<?php

namespace App\Jobs;

use App\Models\Assessment;
use App\Models\Classes;
use App\Models\Grade;
use App\Services\GradeService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;

class RecalculateQuarterGradesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [1, 5, 10]; // Backoff strategy for retries

    public function __construct(
        public int $classId,
        public int $subjectId,
        public int $quarter,
        public int $teacherId,
        public int $schoolYearId,
        public ?array $studentIds = null
    ) {}

    public function middleware(): array
    {
        return [
            // Prevent overlapping jobs for the exact same grade scope.
            // Expires after 180 seconds if it hangs.
            (new WithoutOverlapping("recalc:{$this->classId}:{$this->subjectId}:{$this->quarter}:{$this->teacherId}:{$this->schoolYearId}"))
                ->expireAfter(180),
        ];
    }

    public function handle(\App\Services\AssessmentWeightService $assessmentWeightService): void
    {
        // Eager load students and their profiles for this specific school year
        $class = Classes::with(['students' => function ($query) {
            $query->with(['profiles' => function ($q) {
                $q->where('school_year_id', $this->schoolYearId);
            }]);
        }])->find($this->classId);

        if (! $class) {
            return;
        }

        // Fetch all assessments for this class/subject/quarter with their scores
        // We removed the teacher_id filter to match original cross-teacher weighting logic
        $assessments = Assessment::with('scores')
            ->where('class_id', $this->classId)
            ->where('subject_id', $this->subjectId)
            ->where('quarter', $this->quarter)
            ->get();

        $students = $class->students;
        if ($this->studentIds !== null) {
            $students = $students->whereIn('id', $this->studentIds);
        }

        if ($assessments->isEmpty()) {
            // If no assessments exist, set grades to 0 for enrolled students.
            $gradesToUpsert = [];
            foreach ($students as $student) {
                $profile = $student->profiles->first();
                $gradesToUpsert[] = [
                    'student_id' => $student->id,
                    'subject_id' => $this->subjectId,
                    'quarter' => (string) $this->quarter,
                    'teacher_id' => $this->teacherId,
                    'school_year_id' => $this->schoolYearId,
                    'grade' => 0.0,
                    'student_profile_id' => $profile ? $profile->id : null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            if (! empty($gradesToUpsert)) {
                Grade::upsert(
                    $gradesToUpsert,
                    ['student_id', 'subject_id', 'quarter', 'school_year_id', 'teacher_id'], // unique columns
                    ['grade', 'student_profile_id', 'updated_at'] // update columns
                );
            }

            return;
        }

        // Group assessments by type for weighting
        $grouped = $assessments->groupBy('type');

        // Merge oral_participation into performance_tasks for weighting calculation
        $ops = $grouped->get('oral_participation', collect());
        if ($ops->isNotEmpty()) {
            $pts = $grouped->get('performance_tasks', collect());
            $grouped->put('performance_tasks', $pts->merge($ops));
        }

        $gradesToUpsert = [];

        $weights = $assessmentWeightService->getDecimalWeights($class, $this->subjectId);

        foreach ($students as $student) {
            $profile = $student->profiles->first();
            $typePercentages = array_fill_keys(array_keys($weights), 0.0);
            $typeWeightedScores = array_fill_keys(array_keys($weights), 0.0);

            foreach ($weights as $type => $weight) {
                $typeAssessments = $grouped->get($type, collect());
                $totalScore = 0.0;
                $totalMax = 0.0;
                foreach ($typeAssessments as $assessment) {
                    $scoreModel = null;
                    if ($profile) {
                        $scoreModel = $assessment->scores->firstWhere('student_profile_id', $profile->id);
                    }
                    if (! $scoreModel) {
                        $scoreModel = $assessment->scores->firstWhere('student_id', $student->id);
                    }

                    $scoreValue = $scoreModel ? (float) $scoreModel->score : 0.0;
                    $maxScore = (float) $assessment->max_score;
                    if ($maxScore > 0) {
                        $totalScore += $scoreValue;
                        $totalMax += $maxScore;
                    }
                }
                $typePercentages[$type] = $totalMax > 0
                    ? round(($totalScore / $totalMax) * 100.0, 2)
                    : 0.0;

                $typeWeightedScores[$type] = round($typePercentages[$type] * $weight, 2);
            }

            $initialGrade = round(array_sum($typeWeightedScores), 2);

            // Apply DepEd transmutation to convert initial grade to transmuted grade
            $transmutedGrade = GradeService::transmute($initialGrade);

            $gradesToUpsert[] = [
                'student_id' => $student->id,
                'subject_id' => $this->subjectId,
                'quarter' => (string) $this->quarter,
                'teacher_id' => $this->teacherId,
                'school_year_id' => $this->schoolYearId,
                'grade' => $transmutedGrade,
                'student_profile_id' => $profile ? $profile->id : null,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if (! empty($gradesToUpsert)) {
            Grade::upsert(
                $gradesToUpsert,
                ['student_id', 'subject_id', 'quarter', 'school_year_id', 'teacher_id'], // Unique key constraint
                ['grade', 'student_profile_id', 'updated_at']
            );
        }
    }
}
