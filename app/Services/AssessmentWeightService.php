<?php

namespace App\Services;

use App\Models\Classes;
use App\Models\ClassSubjectWeight;
use App\Models\GradeLevelSubject;

use Illuminate\Support\Facades\Schema;

class AssessmentWeightService
{
    /**
     * Get the weight distribution for a specific class and subject as percentages (e.g. 40 for 40%).
     */
    public function getPercentageWeights(Classes $class, int $subjectId): array
    {
        // 1. Check if there's a custom override for this class and subject
        if (Schema::hasTable('class_subject_weights')) {
            $customWeights = ClassSubjectWeight::where('class_id', $class->id)
                ->where('subject_id', $subjectId)
                ->first();

            if ($customWeights) {
                return [
                    'written_works' => $customWeights->written_works_weight,
                    'performance_tasks' => $customWeights->performance_tasks_weight,
                    'quarterly_assessments' => $customWeights->quarterly_assessments_weight,
                    'is_custom' => true,
                ];
            }
        }

        // 2. Fallback to Grade Level Subject defaults
        if (Schema::hasTable('grade_level_subjects')) {
            $class->loadMissing('section.gradeLevel');
            $gradeLevelId = $class->section?->grade_level_id;

            if ($gradeLevelId) {
                $defaultWeights = GradeLevelSubject::where('grade_level_id', $gradeLevelId)
                    ->where('subject_id', $subjectId)
                    ->first();

                if ($defaultWeights) {
                    return [
                        'written_works' => $defaultWeights->written_works_weight,
                        'performance_tasks' => $defaultWeights->performance_tasks_weight,
                        'quarterly_assessments' => $defaultWeights->quarterly_assessments_weight,
                        'is_custom' => false,
                    ];
                }
            }
        }

        // 3. Absolute defaults if nothing is set in the DB
        $defaults = GradeLevelSubject::DEFAULT_ASSESSMENT_WEIGHTS;

        return [
            'written_works' => $defaults['written_works_weight'],
            'performance_tasks' => $defaults['performance_tasks_weight'],
            'quarterly_assessments' => $defaults['quarterly_assessments_weight'],
            'is_custom' => false,
        ];
    }

    /**
     * Get the weight distribution as decimals for calculation (e.g. 0.40).
     */
    public function getDecimalWeights(Classes $class, int $subjectId): array
    {
        $percentages = $this->getPercentageWeights($class, $subjectId);

        return [
            'written_works' => $percentages['written_works'] / 100.0,
            'performance_tasks' => $percentages['performance_tasks'] / 100.0,
            'quarterly_assessments' => $percentages['quarterly_assessments'] / 100.0,
        ];
    }
}
