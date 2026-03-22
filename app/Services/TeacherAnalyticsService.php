<?php

namespace App\Services;

use App\Models\Classes;
use App\Models\SchoolYear;
use App\Models\Teacher;

class TeacherAnalyticsService
{
    public function resolveAccessibleClassScope(SchoolYear $activeSchoolYear, bool $isTeacherRole, ?Teacher $teacher): array
    {
        if ($isTeacherRole) {
            if (! $teacher) {
                return [
                    'class_ids' => collect(),
                    'mode' => 'none',
                    'can_view_honors' => false,
                    'access_notice' => 'No advisory class handled and no scheduled subjects handled for the current school year.',
                ];
            }

            $advisoryClassIds = Classes::where('teacher_id', $teacher->id)
                ->where('school_year_id', $activeSchoolYear->id)
                ->pluck('id')
                ->unique()
                ->values();

            if ($advisoryClassIds->isNotEmpty()) {
                return [
                    'class_ids' => $advisoryClassIds,
                    'mode' => 'advisory',
                    'can_view_honors' => true,
                    'access_notice' => null,
                ];
            }

            $scheduledClassIds = $teacher->schedules()
                ->whereHas('class', function ($query) use ($activeSchoolYear) {
                    $query->where('school_year_id', $activeSchoolYear->id);
                })
                ->pluck('class_id')
                ->unique()
                ->values();

            if ($scheduledClassIds->isNotEmpty()) {
                return [
                    'class_ids' => $scheduledClassIds,
                    'mode' => 'scheduled',
                    'can_view_honors' => false,
                    'access_notice' => 'No advisory class handled for the current school year. Showing predictions, risk, and top-performing students for scheduled subjects only.',
                ];
            }

            return [
                'class_ids' => collect(),
                'mode' => 'none',
                'can_view_honors' => false,
                'access_notice' => 'No advisory class handled and no scheduled subjects handled for the current school year.',
            ];
        }

        return [
            'class_ids' => Classes::where('school_year_id', $activeSchoolYear->id)
                ->pluck('id')
                ->unique()
                ->values(),
            'mode' => 'all',
            'can_view_honors' => true,
            'access_notice' => null,
        ];
    }
}
