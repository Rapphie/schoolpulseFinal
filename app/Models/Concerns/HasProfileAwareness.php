<?php

namespace App\Models\Concerns;

use App\Models\StudentProfile;

trait HasProfileAwareness
{
    public function scopeProfileAware($query, int $studentId, ?int $schoolYearId = null)
    {
        if ($schoolYearId) {
            $profile = StudentProfile::where('student_id', $studentId)
                ->where('school_year_id', $schoolYearId)
                ->first();

            if ($profile) {
                return $query->where('student_profile_id', $profile->id);
            }

            return $query->where('student_id', $studentId)->where('school_year_id', $schoolYearId);
        }

        return $query->where('student_id', $studentId);
    }
}
