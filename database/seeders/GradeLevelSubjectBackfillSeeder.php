<?php

namespace Database\Seeders;

use App\Models\GradeLevelSubject;
use App\Models\Subject;
use Illuminate\Database\Seeder;

class GradeLevelSubjectBackfillSeeder extends Seeder
{
    public function run(): void
    {
        $subjects = Subject::whereNotNull('grade_level_id')->get();

        foreach ($subjects as $subject) {
            GradeLevelSubject::firstOrCreate(
                [
                    'grade_level_id' => $subject->grade_level_id,
                    'subject_id' => $subject->id,
                ],
                [
                    'is_active' => $subject->is_active ?? true,
                    'written_works_weight' => 40,
                    'performance_tasks_weight' => 40,
                    'quarterly_assessments_weight' => 20,
                ]
            );
        }
    }
}
