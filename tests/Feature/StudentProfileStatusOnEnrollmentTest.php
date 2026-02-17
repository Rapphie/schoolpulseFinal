<?php

namespace Tests\Feature;

use App\Models\Classes;
use App\Models\GradeLevel;
use App\Models\Role;
use App\Models\SchoolYear;
use App\Models\Section;
use App\Models\Student;
use App\Models\StudentProfile;
use App\Models\Teacher;
use App\Models\User;
use App\Services\StudentProfileService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\TestCase;

class StudentProfileStatusOnEnrollmentTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * When a student profile already exists with 'pending' status,
     * enrolling the student should update the profile status to 'enrolled'.
     */
    public function test_pending_profile_is_updated_to_enrolled_on_enrollment(): void
    {
        [$teacher, $schoolYear, $class, $gradeLevel] = $this->createSchoolSetup();

        $guardian = $this->createGuardian();

        $student = Student::create([
            'student_id' => Student::generateStudentId(),
            'lrn' => '999999999999',
            'first_name' => 'Test',
            'last_name' => 'Student',
            'gender' => 'male',
            'birthdate' => '2016-01-01',
            'guardian_id' => $guardian->id,
            'enrollment_date' => now(),
        ]);

        $profile = StudentProfile::create([
            'student_id' => $student->id,
            'school_year_id' => $schoolYear->id,
            'grade_level_id' => $gradeLevel->id,
            'status' => 'pending',
            'created_by_teacher_id' => $teacher->id,
        ]);

        $this->assertSame('pending', $profile->status);

        $service = new StudentProfileService;
        $service->createEnrollmentWithProfile([
            'student_id' => $student->id,
            'class_id' => $class->id,
            'school_year_id' => $schoolYear->id,
            'teacher_id' => $teacher->id,
            'enrolled_by_user_id' => null,
            'status' => 'enrolled',
        ]);

        $profile->refresh();

        $this->assertSame('enrolled', $profile->status);
    }

    /**
     * When no profile exists yet, creating an enrollment should
     * create a new profile with 'enrolled' status.
     */
    public function test_new_profile_is_created_as_enrolled(): void
    {
        [$teacher, $schoolYear, $class, $gradeLevel] = $this->createSchoolSetup();

        $guardian = $this->createGuardian();

        $student = Student::create([
            'student_id' => Student::generateStudentId(),
            'lrn' => '888888888888',
            'first_name' => 'Fresh',
            'last_name' => 'Student',
            'gender' => 'female',
            'birthdate' => '2016-05-10',
            'guardian_id' => $guardian->id,
            'enrollment_date' => now(),
        ]);

        $this->assertNull(
            StudentProfile::where('student_id', $student->id)
                ->where('school_year_id', $schoolYear->id)
                ->first()
        );

        $service = new StudentProfileService;
        $enrollment = $service->createEnrollmentWithProfile([
            'student_id' => $student->id,
            'class_id' => $class->id,
            'school_year_id' => $schoolYear->id,
            'teacher_id' => $teacher->id,
            'enrolled_by_user_id' => null,
            'status' => 'enrolled',
        ]);

        $profile = StudentProfile::where('student_id', $student->id)
            ->where('school_year_id', $schoolYear->id)
            ->first();

        $this->assertNotNull($profile);
        $this->assertSame('enrolled', $profile->status);
        $this->assertSame($profile->id, $enrollment->student_profile_id);
    }

    /**
     * When a profile already has 'enrolled' status, enrolling again
     * should not change the status (idempotent).
     */
    public function test_already_enrolled_profile_stays_enrolled(): void
    {
        [$teacher, $schoolYear, $class, $gradeLevel] = $this->createSchoolSetup();

        $guardian = $this->createGuardian();

        $student = Student::create([
            'student_id' => Student::generateStudentId(),
            'lrn' => '777777777777',
            'first_name' => 'Already',
            'last_name' => 'Enrolled',
            'gender' => 'male',
            'birthdate' => '2016-03-15',
            'guardian_id' => $guardian->id,
            'enrollment_date' => now(),
        ]);

        $profile = StudentProfile::create([
            'student_id' => $student->id,
            'school_year_id' => $schoolYear->id,
            'grade_level_id' => $gradeLevel->id,
            'status' => 'enrolled',
        ]);

        $service = new StudentProfileService;
        $service->createEnrollmentWithProfile([
            'student_id' => $student->id,
            'class_id' => $class->id,
            'school_year_id' => $schoolYear->id,
            'teacher_id' => $teacher->id,
            'enrolled_by_user_id' => null,
            'status' => 'enrolled',
        ]);

        $profile->refresh();

        $this->assertSame('enrolled', $profile->status);
    }

    /**
     * @return array{Teacher, SchoolYear, Classes, GradeLevel}
     */
    private function createSchoolSetup(): array
    {
        Role::firstOrCreate(
            ['id' => 2],
            ['name' => 'teacher', 'description' => 'Teacher role']
        );

        $user = User::factory()->create(['role_id' => 2]);

        $teacher = Teacher::create([
            'user_id' => $user->id,
            'status' => 'active',
        ]);

        $schoolYear = SchoolYear::create([
            'name' => 'SY-'.Str::upper(Str::random(6)),
            'start_date' => now()->startOfMonth()->toDateString(),
            'end_date' => now()->addMonths(10)->endOfMonth()->toDateString(),
            'is_active' => true,
            'is_promotion_open' => false,
        ]);

        $gradeLevel = GradeLevel::create([
            'name' => 'Grade '.random_int(1, 6),
            'level' => random_int(1, 6),
            'description' => 'Test grade level',
        ]);

        $section = Section::create([
            'name' => 'SEC-'.Str::upper(Str::random(5)),
            'grade_level_id' => $gradeLevel->id,
            'description' => 'Test section',
        ]);

        $class = Classes::create([
            'section_id' => $section->id,
            'school_year_id' => $schoolYear->id,
            'teacher_id' => $teacher->id,
            'capacity' => 40,
        ]);

        return [$teacher, $schoolYear, $class, $gradeLevel];
    }

    private function createGuardian(): \App\Models\Guardian
    {
        Role::firstOrCreate(
            ['id' => 3],
            ['name' => 'guardian', 'description' => 'Guardian role']
        );

        $guardianUser = User::factory()->create(['role_id' => 3]);

        return \App\Models\Guardian::create([
            'user_id' => $guardianUser->id,
            'phone' => '09'.fake()->numerify('#########'),
            'relationship' => 'parent',
        ]);
    }
}
