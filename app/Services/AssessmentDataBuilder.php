<?php

namespace App\Services;

use App\Models\Assessment;
use App\Models\Classes;
use App\Models\Student;
use Illuminate\Support\Collection;

class AssessmentDataBuilder
{
    public function buildStudentGradesData(Collection $students, Collection $assessments, Classes $class): Collection
    {
        return $students->map(function ($student) use ($assessments, $class) {
            return $this->buildSingleStudentData($student, $assessments, $class);
        });
    }

    private function buildSingleStudentData(Student $student, Collection $assessments, Classes $class): array
    {
        $studentData = [
            'student' => $student,
            'quarters' => [],
        ];

        for ($quarter = 1; $quarter <= 4; $quarter++) {
            $quarterAssessments = $assessments->get($quarter, collect());

            $quarterData = [
                'written_works' => [],
                'performance_tasks' => [],
                'quarterly_assessments' => [],
            ];

            foreach (['written_works', 'performance_tasks', 'quarterly_assessments'] as $type) {
                $typeAssessments = $quarterAssessments->get($type, collect());

                foreach ($typeAssessments as $assessment) {
                    $quarterData[$type][] = $this->buildAssessmentScoreData(
                        $student,
                        $assessment,
                        $assessments,
                        $quarter,
                        $class->school_year_id
                    );
                }
            }

            $studentData['quarters'][$quarter] = $quarterData;
        }

        return $studentData;
    }

    private function buildAssessmentScoreData(
        Student $student,
        Assessment $assessment,
        Collection $assessments,
        int $quarter,
        int $schoolYearId
    ): array {
        $scoreValue = null;
        $maxScoreValue = $assessment->max_score;

        if ($assessment->id === -999) {
            $scoreValue = $this->calculateConsolidatedOPScore($student, $assessments, $quarter, $schoolYearId);
        } else {
            $scoreValue = $this->getNormalScore($student, $assessment, $schoolYearId);
        }

        return [
            'assessment' => $assessment,
            'score' => $scoreValue,
            'max_score' => $maxScoreValue,
            'percentage' => $scoreValue !== null && $maxScoreValue > 0
                ? ($scoreValue / $maxScoreValue) * 100
                : null,
        ];
    }

    private function calculateConsolidatedOPScore(
        Student $student,
        Collection $assessments,
        int $quarter,
        int $schoolYearId
    ): ?float {
        $quarterOps = $assessments->get($quarter, collect())->get('oral_participation', collect());

        if ($quarterOps->isEmpty()) {
            return null;
        }

        $totalScore = 0;
        $hasAnyScore = false;

        foreach ($quarterOps as $op) {
            $profile = $student->profileFor($schoolYearId);
            $score = null;

            if ($profile) {
                $score = $op->scores->firstWhere('student_profile_id', $profile->id);
            }

            if (! $score) {
                $score = $op->scores->firstWhere('student_id', $student->id);
            }

            if ($score) {
                $totalScore += $score->score;
                $hasAnyScore = true;
            }
        }

        return $hasAnyScore ? $totalScore : null;
    }

    private function getNormalScore(Student $student, Assessment $assessment, int $schoolYearId): ?float
    {
        $profile = $student->profileFor($schoolYearId);
        $score = null;

        if ($profile) {
            $score = $assessment->scores->firstWhere('student_profile_id', $profile->id);
        }

        if (! $score) {
            $score = $assessment->scores->firstWhere('student_id', $student->id);
        }

        return $score ? $score->score : null;
    }
}
