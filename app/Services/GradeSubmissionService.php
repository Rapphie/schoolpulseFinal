<?php

namespace App\Services;

use App\Models\Assessment;
use App\Models\AssessmentScore;
use App\Models\Classes;
use App\Models\Teacher;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class GradeSubmissionService
{
    public function __construct(
        private QuarterLockService $quarterLockService
    ) {}

    public function submitBatch(Classes $class, ?Teacher $teacher, array $grades): GradeSubmissionResult
    {
        if (! $teacher) {
            return new GradeSubmissionResult(
                success: false,
                message: 'Authenticated user is not a teacher.',
                savedCount: 0,
                clearedCount: 0,
                rejectedCells: [],
                statusCode: 403
            );
        }

        $quarterLockContext = $this->quarterLockService->contextForSchoolYear((int) $class->school_year_id);
        $quarterLocks = $quarterLockContext['quarterLocks'];

        $validatedGrades = $grades;
        $assessmentIds = collect($validatedGrades)->pluck('assessment_id')->unique();
        $studentIds = collect($validatedGrades)->pluck('student_id')->unique();

        $assessments = Assessment::whereIn('id', $assessmentIds)
            ->where('class_id', $class->id)
            ->where(function ($query) use ($teacher) {
                $query->where('teacher_id', $teacher->id)
                    ->orWhere('type', 'oral_participation');
            })
            ->get()
            ->keyBy('id');

        $students = $class->students()
            ->with(['profiles' => function ($q) use ($class) {
                $q->where('school_year_id', $class->school_year_id);
            }])
            ->whereIn('students.id', $studentIds)
            ->get()
            ->keyBy('id');

        $successCount = 0;
        $clearedCount = 0;
        $rejectedCells = [];
        $affectedCombos = [];

        $scoresToUpsert = [];
        $scoresToDelete = [];

        foreach ($validatedGrades as $gradeData) {
            $result = $this->processGradeCell(
                $gradeData,
                $assessments,
                $students,
                $quarterLocks,
                $class
            );

            if (isset($result['rejected'])) {
                $rejectedCells[] = $result['rejected'];

                continue;
            }

            if (isset($result['deleted'])) {
                $scoresToDelete[] = $result['deleted'];
                $clearedCount++;
            } elseif (isset($result['upsert'])) {
                $scoresToUpsert[] = $result['upsert'];
                $successCount++;
            }

            if (isset($result['affectedCombo'])) {
                $key = $result['affectedCombo']['key'];
                if (! isset($affectedCombos[$key])) {
                    $affectedCombos[$key] = $result['affectedCombo']['data'];
                }
            }
        }

        $this->persistGradeChanges(
            $scoresToUpsert,
            $scoresToDelete,
            $class,
            $affectedCombos,
            $studentIds->toArray()
        );

        $summaryParts = [];
        if ($successCount > 0) {
            $summaryParts[] = "{$successCount} grades saved";
        }
        if ($clearedCount > 0) {
            $summaryParts[] = "{$clearedCount} grades cleared";
        }
        if (count($rejectedCells) > 0) {
            $summaryParts[] = count($rejectedCells).' grade entries rejected';
        }

        return new GradeSubmissionResult(
            success: true,
            message: empty($summaryParts) ? 'No grade changes were saved.' : implode(', ', $summaryParts).'.',
            savedCount: $successCount,
            clearedCount: $clearedCount,
            rejectedCells: $rejectedCells
        );
    }

    private function processGradeCell(
        array $gradeData,
        Collection $assessments,
        Collection $students,
        array $quarterLocks,
        Classes $class
    ): array {
        $assessment = $assessments->get($gradeData['assessment_id']);

        if (! $assessment) {
            return ['rejected' => [
                'student_id' => (int) $gradeData['student_id'],
                'assessment_id' => (int) $gradeData['assessment_id'],
                'reason' => 'Assessment is not accessible for this class/teacher.',
            ]];
        }

        $assessmentQuarter = (int) $assessment->quarter;
        if (($quarterLocks[$assessmentQuarter]['is_locked'] ?? false) === true) {
            return ['rejected' => [
                'student_id' => (int) $gradeData['student_id'],
                'assessment_id' => (int) $gradeData['assessment_id'],
                'reason' => "Quarter {$assessmentQuarter} is locked. Grade changes are disabled.",
            ]];
        }

        $student = $students->get($gradeData['student_id']);
        if (! $student) {
            return ['rejected' => [
                'student_id' => (int) $gradeData['student_id'],
                'assessment_id' => (int) $gradeData['assessment_id'],
                'reason' => 'Student record was not found.',
            ]];
        }

        $profile = $student->profiles->first();
        $scoreValue = $gradeData['score'];

        if ($scoreValue === null || $scoreValue === '') {
            return [
                'deleted' => [
                    'assessment_id' => $assessment->id,
                    'student_id' => $student->id,
                ],
                'affectedCombo' => $this->buildAffectedCombo($assessment),
            ];
        }

        if ($assessment->max_score === null) {
            return ['rejected' => [
                'student_id' => (int) $gradeData['student_id'],
                'assessment_id' => (int) $gradeData['assessment_id'],
                'reason' => 'Set a maximum score before entering student scores.',
            ]];
        }

        if ((float) $scoreValue > (float) $assessment->max_score) {
            return ['rejected' => [
                'student_id' => (int) $gradeData['student_id'],
                'assessment_id' => (int) $gradeData['assessment_id'],
                'reason' => 'Score exceeds the maximum allowed score of '.$assessment->max_score.'.',
            ]];
        }

        return [
            'upsert' => [
                'assessment_id' => $assessment->id,
                'student_id' => $student->id,
                'score' => $scoreValue,
                'remarks' => null,
                'student_profile_id' => $profile ? $profile->id : null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            'affectedCombo' => $this->buildAffectedCombo($assessment),
        ];
    }

    private function buildAffectedCombo(Assessment $assessment): array
    {
        $key = $assessment->subject_id.'-'.$assessment->quarter;

        return [
            'key' => $key,
            'data' => [
                'subject_id' => $assessment->subject_id,
                'quarter' => (int) $assessment->quarter,
                'teacher_id' => $assessment->teacher_id,
            ],
        ];
    }

    private function persistGradeChanges(
        array $scoresToUpsert,
        array $scoresToDelete,
        Classes $class,
        array $affectedCombos,
        array $studentIds
    ): void {
        DB::transaction(function () use ($scoresToUpsert, $scoresToDelete, $class, $affectedCombos, $studentIds) {
            if (! empty($scoresToDelete)) {
                foreach (collect($scoresToDelete)->groupBy('assessment_id') as $assessmentId => $deletes) {
                    AssessmentScore::where('assessment_id', $assessmentId)
                        ->whereIn('student_id', $deletes->pluck('student_id'))
                        ->delete();
                }
            }

            if (! empty($scoresToUpsert)) {
                AssessmentScore::upsert(
                    $scoresToUpsert,
                    ['assessment_id', 'student_id'],
                    ['score', 'remarks', 'student_profile_id', 'updated_at']
                );
            }

            foreach ($affectedCombos as $combo) {
                \App\Jobs\RecalculateQuarterGradesJob::dispatch(
                    $class->id,
                    $combo['subject_id'],
                    $combo['quarter'],
                    $combo['teacher_id'],
                    $class->school_year_id,
                    $studentIds
                )->afterCommit();
            }
        }, 5);
    }
}
