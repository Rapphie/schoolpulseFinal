<?php

namespace App\Services;

use App\Models\Classes;
use App\Models\Enrollment;
use App\Models\SchoolYear;
use App\Models\Student;
use App\Models\StudentProfile;
use Illuminate\Support\Facades\Log;

class StudentProfileService
{
    /**
     * Create or retrieve a student profile and link it to the enrollment.
     *
     * This ensures each student has exactly one profile per school year,
     * capturing their grade level for that academic year.
     */
    public function ensureProfileForEnrollment(Student $student, Classes $class, int $schoolYearId): StudentProfile
    {
        // Determine grade level from the class's section
        $gradeLevelId = $class->section?->grade_level_id;

        if (! $gradeLevelId) {
            throw new \InvalidArgumentException('Cannot determine grade level: class has no section or section has no grade level.');
        }

        // Find or create the profile for this student + school year
        $profile = StudentProfile::firstOrCreate(
            [
                'student_id' => $student->id,
                'school_year_id' => $schoolYearId,
            ],
            [
                'grade_level_id' => $gradeLevelId,
                'status' => 'enrolled',
            ]
        );

        // If profile already existed but grade level differs (e.g., transfer to different grade),
        // update it if the new enrollment is more recent or specific
        if ($profile->grade_level_id !== $gradeLevelId && $profile->wasRecentlyCreated === false) {
            // Log or handle potential grade level mismatch
            // For now, we keep the first recorded grade level for the year
        }

        return $profile;
    }

    /**
     * Create an enrollment with an automatically linked student profile.
     *
     * @param  array  $enrollmentData  Must include: student_id, class_id, school_year_id
     */
    public function createEnrollmentWithProfile(array $enrollmentData): Enrollment
    {
        $student = Student::findOrFail($enrollmentData['student_id']);
        $class = Classes::with('section')->findOrFail($enrollmentData['class_id']);
        // Resolve the intended school year
        // We prioritize the class's school year or the explicitly provided school year ID.
        // This allows enrollment into future school years even if they are not yet "active".
        $providedSchoolYearId = $enrollmentData['school_year_id'] ?? null;
        $classSchoolYearId = $class->school_year_id ?? null;

        $schoolYearId = $providedSchoolYearId ?? $classSchoolYearId;

        if (! $schoolYearId) {
            // Fallback to active school year if neither class nor explicit ID provides one
            $activeSchoolYear = SchoolYear::where('is_active', true)->first();
            if ($activeSchoolYear) {
                $schoolYearId = $activeSchoolYear->id;
            } else {
                throw new \RuntimeException('Cannot determine school year for enrollment. No class school year, no provided ID, and no active school year.');
            }
        }

        $schoolYear = SchoolYear::find($schoolYearId);

        if (! $schoolYear || (! $schoolYear->is_active && ! $schoolYear->is_promotion_open)) {
            throw new \RuntimeException('Enrollment for this school year is not open.');
        }

        // Ensure profile exists for the resolved school year
        $profile = $this->ensureProfileForEnrollment($student, $class, $schoolYearId);

        // Auto-update the previous year's profile status to "promoted" if the student
        // is advancing to a higher grade level in a new school year.
        $this->markPreviousProfileAsPromoted($student, $schoolYearId, $profile->grade_level_id);

        // Add profile link to enrollment data
        $enrollmentData['student_profile_id'] = $profile->id;

        return Enrollment::create($enrollmentData);
    }

    /**
     * Update final average for a student profile (typically at end of school year).
     */
    public function updateFinalAverage(StudentProfile $profile, float $average, ?string $status = null): StudentProfile
    {
        $profile->final_average = $average;

        if ($status) {
            $profile->status = $status;
        }

        $profile->save();

        return $profile;
    }

    /**
     * Mark the most recent previous profile as "promoted" when a student
     * is enrolled into a higher grade level in a new school year.
     */
    private function markPreviousProfileAsPromoted(Student $student, int $currentSchoolYearId, int $newGradeLevelId): void
    {
        $previousProfile = StudentProfile::where('student_id', $student->id)
            ->where('school_year_id', '!=', $currentSchoolYearId)
            ->whereIn('status', ['enrolled', 'active', 'pending'])
            ->orderByDesc('school_year_id')
            ->first();

        if (! $previousProfile) {
            return;
        }

        $previousGradeLevel = $previousProfile->gradeLevel;
        $newGradeLevel = \App\Models\GradeLevel::find($newGradeLevelId);

        if ($previousGradeLevel && $newGradeLevel && $newGradeLevel->level > $previousGradeLevel->level) {
            $previousProfile->update(['status' => 'promoted']);
            Log::info("Student #{$student->id} profile #{$previousProfile->id} auto-marked as promoted.");
        }
    }

    /**
     * Get grade level progression history for a student.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getGradeHistory(int $studentId)
    {
        return StudentProfile::where('student_id', $studentId)
            ->with(['schoolYear', 'gradeLevel'])
            ->orderBy('school_year_id', 'desc')
            ->get();
    }
}
