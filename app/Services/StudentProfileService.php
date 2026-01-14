<?php

namespace App\Services;

use App\Models\Classes;
use App\Models\Enrollment;
use App\Models\Student;
use App\Models\StudentProfile;

class StudentProfileService
{
    /**
     * Create or retrieve a student profile and link it to the enrollment.
     *
     * This ensures each student has exactly one profile per school year,
     * capturing their grade level for that academic year.
     *
     * @param Student $student
     * @param Classes $class
     * @param int $schoolYearId
     * @return StudentProfile
     */
    public function ensureProfileForEnrollment(Student $student, Classes $class, int $schoolYearId): StudentProfile
    {
        // Determine grade level from the class's section
        $gradeLevelId = $class->section?->grade_level_id;

        if (!$gradeLevelId) {
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
                'status' => 'active',
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
     * @param array $enrollmentData Must include: student_id, class_id, school_year_id
     * @return Enrollment
     */
    public function createEnrollmentWithProfile(array $enrollmentData): Enrollment
    {
        $student = Student::findOrFail($enrollmentData['student_id']);
        $class = Classes::with('section')->findOrFail($enrollmentData['class_id']);
        $schoolYearId = $enrollmentData['school_year_id'];

        // Ensure profile exists
        $profile = $this->ensureProfileForEnrollment($student, $class, $schoolYearId);

        // Add profile link to enrollment data
        $enrollmentData['student_profile_id'] = $profile->id;

        return Enrollment::create($enrollmentData);
    }

    /**
     * Update final average for a student profile (typically at end of school year).
     *
     * @param StudentProfile $profile
     * @param float $average
     * @param string|null $status
     * @return StudentProfile
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
     * Get grade level progression history for a student.
     *
     * @param int $studentId
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
