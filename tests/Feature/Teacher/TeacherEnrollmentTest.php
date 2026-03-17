<?php

namespace Tests\Feature\Teacher;

use App\Models\Classes;
use App\Models\Enrollment;
use App\Models\GradeLevel;
use App\Models\Guardian;
use App\Models\Role;
use App\Models\SchoolYear;
use App\Models\SchoolYearQuarter;
use App\Models\Section;
use App\Models\Setting;
use App\Models\Student;
use App\Models\StudentProfile;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\TestCase;

class TeacherEnrollmentTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class);

        SchoolYearQuarter::query()->update(['is_manually_set_active' => false]);
        SchoolYear::query()->update(['is_active' => false]);
    }

    public function test_teacher_can_enroll_new_student_successfully(): void
    {
        Mail::fake();
        Cache::flush();

        [$teacher, $teacherUser] = $this->createTeacherUser();
        [$schoolYear, $class] = $this->createOpenClassWithAdviser($teacher);
        $this->enableTeacherEnrollment();

        $response = $this->actingAs($teacherUser)
            ->from(route('teacher.enrollment.index'))
            ->post(route('teacher.enrollment.store'), [
                'class_id' => $class->id,
                'lrn' => fake()->unique()->numerify('############'),
                'first_name' => 'NewEnroll',
                'last_name' => 'Student',
                'gender' => 'female',
                'birthdate' => '2016-03-15',
                'address' => '123 Test St',
                'guardian_first_name' => 'Parent',
                'guardian_last_name' => 'Test',
                'guardian_email' => 'guardian.new.'.Str::lower(Str::random(8)).'@example.com',
                'guardian_phone' => '09123456789',
                'guardian_relationship' => 'parent',
                'enrollment_status' => 'enrolled',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $enrollment = Enrollment::query()
            ->where('class_id', $class->id)
            ->latest('id')
            ->first();

        $this->assertNotNull($enrollment);
        $this->assertSame($teacher->id, $enrollment->teacher_id);
        $this->assertSame($teacherUser->id, $enrollment->enrolled_by_user_id);
        $this->assertSame($schoolYear->id, $enrollment->school_year_id);
        $this->assertSame('enrolled', $enrollment->status);
    }

    public function test_teacher_can_enroll_multiple_students_without_guardian_email(): void
    {
        Mail::fake();
        Cache::flush();

        [$teacher, $teacherUser] = $this->createTeacherUser();
        [$schoolYear, $class] = $this->createOpenClassWithAdviser($teacher);
        $this->enableTeacherEnrollment();
        $nullEmailUsersBefore = User::query()->whereNull('email')->count();

        $firstResponse = $this->actingAs($teacherUser)
            ->from(route('teacher.enrollment.index'))
            ->post(route('teacher.enrollment.store'), [
                'class_id' => $class->id,
                'lrn' => fake()->unique()->numerify('############'),
                'first_name' => 'FirstBlank',
                'last_name' => 'Guardian',
                'gender' => 'female',
                'birthdate' => '2016-03-15',
                'address' => '123 Test St',
                'guardian_first_name' => 'Parent',
                'guardian_last_name' => 'One',
                'guardian_email' => '',
                'guardian_phone' => '',
                'guardian_relationship' => 'parent',
                'enrollment_status' => 'enrolled',
            ]);

        $firstResponse->assertRedirect();
        $firstResponse->assertSessionHas('success');

        $secondResponse = $this->actingAs($teacherUser)
            ->from(route('teacher.enrollment.index'))
            ->post(route('teacher.enrollment.store'), [
                'class_id' => $class->id,
                'lrn' => fake()->unique()->numerify('############'),
                'first_name' => 'SecondBlank',
                'last_name' => 'Guardian',
                'gender' => 'male',
                'birthdate' => '2016-03-16',
                'address' => '456 Test St',
                'guardian_first_name' => 'Parent',
                'guardian_last_name' => 'Two',
                'guardian_email' => '',
                'guardian_phone' => '',
                'guardian_relationship' => 'guardian',
                'enrollment_status' => 'enrolled',
            ]);

        $secondResponse->assertRedirect();
        $secondResponse->assertSessionHas('success');
        $this->assertSame($nullEmailUsersBefore + 2, User::query()->whereNull('email')->count());
        Mail::assertNothingQueued();
    }

    public function test_teacher_can_search_guardians_for_dropdown(): void
    {
        Cache::flush();

        [$teacher, $teacherUser] = $this->createTeacherUser();
        [$schoolYear, $class] = $this->createOpenClassWithAdviser($teacher);
        $this->enableTeacherEnrollment();

        $guardianRole = Role::firstOrCreate(
            ['name' => 'guardian'],
            ['description' => 'Guardian role']
        );
        $guardianUser = User::factory()->create([
            'role_id' => $guardianRole->id,
            'email' => 'teacher.search.guardian.'.Str::lower(Str::random(8)).'@example.com',
        ]);
        $guardian = Guardian::create([
            'user_id' => $guardianUser->id,
            'phone' => '09179998888',
            'relationship' => 'parent',
        ]);
        $student = Student::create([
            'student_id' => Student::generateStudentId($schoolYear),
            'first_name' => 'Linked',
            'last_name' => 'Pupil',
            'gender' => 'female',
            'birthdate' => '2016-04-01',
            'guardian_id' => $guardian->id,
            'enrollment_date' => now(),
        ]);

        $response = $this->actingAs($teacherUser)
            ->getJson(route('teacher.enrollment.guardian.search', ['q' => 'teacher.search.guardian']));

        $response->assertOk();
        $guardians = collect($response->json('guardians'));
        $matchedGuardian = $guardians->firstWhere('id', $guardian->id);

        $this->assertNotNull($matchedGuardian);
        $this->assertSame($guardianUser->email, $matchedGuardian['email'] ?? null);
        $this->assertSame($student->first_name, $matchedGuardian['connected_student']['first_name'] ?? null);
    }

    public function test_teacher_can_enroll_new_student_with_existing_guardian_credentials(): void
    {
        Mail::fake();
        Cache::flush();

        [$teacher, $teacherUser] = $this->createTeacherUser();
        [$schoolYear, $class] = $this->createOpenClassWithAdviser($teacher);
        $this->enableTeacherEnrollment();

        $guardianRole = Role::firstOrCreate(
            ['name' => 'guardian'],
            ['description' => 'Guardian role']
        );
        $guardianUser = User::factory()->create([
            'role_id' => $guardianRole->id,
            'email' => 'teacher.shared.guardian.'.Str::lower(Str::random(8)).'@example.com',
            'first_name' => 'Shared',
            'last_name' => 'Guardian',
        ]);
        $guardian = Guardian::create([
            'user_id' => $guardianUser->id,
            'phone' => '09172223333',
            'relationship' => 'parent',
        ]);
        Student::create([
            'student_id' => Student::generateStudentId($schoolYear),
            'first_name' => 'Older',
            'last_name' => 'Sibling',
            'gender' => 'male',
            'birthdate' => '2015-09-10',
            'guardian_id' => $guardian->id,
            'enrollment_date' => now(),
        ]);

        $userCountBefore = User::count();
        $guardianCountBefore = Guardian::count();

        $response = $this->actingAs($teacherUser)
            ->from(route('teacher.enrollment.index'))
            ->post(route('teacher.enrollment.store'), [
                'class_id' => $class->id,
                'use_existing_guardian' => 1,
                'guardian_id' => $guardian->id,
                'first_name' => 'Younger',
                'last_name' => 'Sibling',
                'gender' => 'female',
                'birthdate' => '2016-03-15',
                'address' => '123 Test St',
                'guardian_first_name' => 'Shared',
                'guardian_last_name' => 'Guardian',
                'guardian_email' => $guardianUser->email,
                'guardian_phone' => '09172223333',
                'guardian_relationship' => 'parent',
                'enrollment_status' => 'enrolled',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', function (string $message) {
            return str_contains($message, 'existing credentials were used');
        });

        $this->assertDatabaseHas('students', [
            'first_name' => 'Younger',
            'last_name' => 'Sibling',
            'guardian_id' => $guardian->id,
        ]);
        $this->assertSame($userCountBefore, User::count());
        $this->assertSame($guardianCountBefore, Guardian::count());
        Mail::assertNothingSent();
    }

    public function test_teacher_can_enroll_new_student_as_transferred(): void
    {
        Mail::fake();
        Cache::flush();

        [$teacher, $teacherUser] = $this->createTeacherUser();
        [$schoolYear, $class] = $this->createOpenClassWithAdviser($teacher);
        $this->enableTeacherEnrollment();

        $response = $this->actingAs($teacherUser)
            ->from(route('teacher.enrollment.index'))
            ->post(route('teacher.enrollment.store'), [
                'class_id' => $class->id,
                'first_name' => 'Transfer',
                'last_name' => 'Student',
                'gender' => 'male',
                'birthdate' => '2015-06-01',
                'address' => '456 Test Ave',
                'guardian_first_name' => 'Guardian',
                'guardian_last_name' => 'Transfer',
                'guardian_email' => 'guardian.transfer.'.Str::lower(Str::random(8)).'@example.com',
                'guardian_phone' => '09198765432',
                'guardian_relationship' => 'guardian',
                'enrollment_status' => 'transferred',
            ]);

        $response->assertRedirect();
        // Check for success session
        $response->assertSessionMissing('error');
        $response->assertSessionHas('success');

        $enrollment = Enrollment::query()
            ->where('class_id', $class->id)
            ->latest('id')
            ->first();

        $this->assertNotNull($enrollment);
        $this->assertSame('transferred', $enrollment->status);
    }

    public function test_teacher_can_reenroll_past_student(): void
    {
        Cache::flush();

        [$teacher, $teacherUser] = $this->createTeacherUser();
        [$schoolYear, $class] = $this->createOpenClassWithAdviser($teacher);
        $schoolYear->update(['is_promotion_open' => true]);
        $this->enableTeacherEnrollment();

        $student = Student::create([
            'student_id' => Student::generateStudentId(),
            'first_name' => 'Returning',
            'last_name' => 'Student',
            'gender' => 'female',
            'birthdate' => '2015-05-11',
            'enrollment_date' => now(),
        ]);

        $response = $this->actingAs($teacherUser)
            ->post(route('teacher.enrollment.storePastStudent'), [
                'student_id' => (string) $student->id,
                'class_id' => $class->id,
                'enrollment_status' => 'enrolled',
            ]);

        $response->assertRedirect(route('teacher.enrollment.index', ['school_year_id' => $schoolYear->id]));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('enrollments', [
            'student_id' => $student->id,
            'class_id' => $class->id,
            'school_year_id' => $schoolYear->id,
            'teacher_id' => $teacher->id,
            'enrolled_by_user_id' => $teacherUser->id,
            'status' => 'enrolled',
        ]);
    }

    public function test_teacher_store_past_student_pending_profile_enrollment_succeeds_for_active_school_year_when_promotion_closed(): void
    {
        Cache::flush();

        [$teacher, $teacherUser] = $this->createTeacherUser();
        [$schoolYear, $class] = $this->createOpenClassWithAdviser($teacher);
        $this->enableTeacherEnrollment();

        $student = Student::create([
            'student_id' => Student::generateStudentId(),
            'first_name' => 'Pending',
            'last_name' => 'Profile',
            'gender' => 'female',
            'birthdate' => '2015-08-10',
            'enrollment_date' => now(),
        ]);

        $class->load('section');
        $profile = StudentProfile::create([
            'student_id' => $student->id,
            'school_year_id' => $schoolYear->id,
            'grade_level_id' => $class->section->grade_level_id,
            'status' => 'pending',
            'created_by_teacher_id' => $teacher->id,
        ]);

        $this->assertSame('pending', $profile->status);

        $response = $this->actingAs($teacherUser)
            ->post(route('teacher.enrollment.storePastStudent'), [
                'student_type' => 'enroll',
                'student_id' => (string) $student->id,
                'class_id' => $class->id,
                'enrollment_status' => 'enrolled',
            ]);

        $response->assertRedirect(route('teacher.enrollment.index', ['school_year_id' => $schoolYear->id]));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('enrollments', [
            'student_id' => $student->id,
            'class_id' => $class->id,
            'school_year_id' => $schoolYear->id,
            'teacher_id' => $teacher->id,
            'enrolled_by_user_id' => $teacherUser->id,
            'status' => 'enrolled',
        ]);

        $profile->refresh();
        $this->assertSame('enrolled', $profile->status);
    }

    public function test_teacher_store_past_student_returning_enrollment_requires_promotion_when_closed(): void
    {
        Cache::flush();

        [$teacher, $teacherUser] = $this->createTeacherUser();
        [$schoolYear, $class] = $this->createOpenClassWithAdviser($teacher);
        $this->enableTeacherEnrollment();

        $student = Student::create([
            'student_id' => Student::generateStudentId(),
            'first_name' => 'Returning',
            'last_name' => 'Blocked',
            'gender' => 'male',
            'birthdate' => '2015-07-07',
            'enrollment_date' => now(),
        ]);

        $response = $this->actingAs($teacherUser)
            ->from(route('teacher.enrollment.index', ['school_year_id' => $schoolYear->id]))
            ->post(route('teacher.enrollment.storePastStudent'), [
                'student_type' => 'returning',
                'student_id' => (string) $student->id,
                'class_id' => $class->id,
                'enrollment_status' => 'enrolled',
            ]);

        $response->assertRedirect(route('teacher.enrollment.index', ['school_year_id' => $schoolYear->id]));
        $response->assertSessionHas('error', 'Promotion must be enabled to enroll past students.');

        $this->assertDatabaseMissing('enrollments', [
            'student_id' => $student->id,
            'class_id' => $class->id,
            'school_year_id' => $schoolYear->id,
        ]);
    }

    public function test_teacher_enrollment_fails_with_missing_required_fields(): void
    {
        Cache::flush();

        [$teacher, $teacherUser] = $this->createTeacherUser();
        [$schoolYear, $class] = $this->createOpenClassWithAdviser($teacher);
        $this->enableTeacherEnrollment();

        $response = $this->actingAs($teacherUser)
            ->from(route('teacher.enrollment.index'))
            ->post(route('teacher.enrollment.store'), [
                'class_id' => $class->id,
                'first_name' => '',
                'last_name' => 'Validation',
                'gender' => 'male',
                'birthdate' => '2016-02-01',
                'guardian_first_name' => 'Guardian',
                'guardian_last_name' => 'Validation',
                'guardian_email' => 'guardian.val.'.Str::lower(Str::random(8)).'@example.com',
                'guardian_phone' => '09120000001',
                'guardian_relationship' => 'parent',
            ]);

        $response->assertRedirect(route('teacher.enrollment.index'));
        $response->assertSessionHasErrors(['first_name']);
    }

    public function test_teacher_enrollment_rejects_full_class(): void
    {
        Cache::flush();

        [$teacher, $teacherUser] = $this->createTeacherUser();
        [$schoolYear, $class] = $this->createOpenClassWithAdviser($teacher);
        $schoolYear->update(['is_promotion_open' => true]);
        $class->update(['capacity' => 0]);
        $this->enableTeacherEnrollment();

        $student = Student::create([
            'student_id' => Student::generateStudentId(),
            'first_name' => 'NoRoom',
            'last_name' => 'Student',
            'gender' => 'male',
            'birthdate' => '2016-01-01',
            'enrollment_date' => now(),
        ]);

        $response = $this->actingAs($teacherUser)
            ->post(route('teacher.enrollment.storePastStudent'), [
                'student_id' => (string) $student->id,
                'class_id' => $class->id,
                'enrollment_status' => 'enrolled',
            ]);

        $response->assertRedirect(route('teacher.enrollment.index', ['school_year_id' => $schoolYear->id]));
        $response->assertSessionHas('error');
    }

    public function test_teacher_enrollment_blocked_when_enrollment_disabled(): void
    {
        Cache::flush();

        [$teacher, $teacherUser] = $this->createTeacherUser();
        $this->disableTeacherEnrollment();

        $response = $this->actingAs($teacherUser)
            ->get(route('teacher.enrollment.index'));

        $response->assertRedirect(route('teacher.dashboard'));
        $response->assertSessionHas('error');
    }

    /**
     * @return array{Teacher, User}
     */
    private function createTeacherUser(): array
    {
        $teacherRole = Role::firstOrCreate(
            ['name' => 'teacher'],
            ['description' => 'Teacher role']
        );

        $user = User::factory()->create([
            'role_id' => $teacherRole->id,
        ]);

        $teacher = Teacher::create([
            'user_id' => $user->id,
            'department' => 'General',
            'status' => 'active',
        ]);

        return [$teacher, $user];
    }

    /**
     * @return array{SchoolYear, Classes}
     */
    private function createOpenClassWithAdviser(Teacher $teacher): array
    {
        $schoolYear = SchoolYear::create([
            'name' => 'SY-'.Str::upper(Str::random(6)),
            'start_date' => now()->startOfMonth()->toDateString(),
            'end_date' => now()->addMonths(10)->endOfMonth()->toDateString(),
            'is_active' => true,
            'is_promotion_open' => false,
        ]);

        SchoolYearQuarter::create([
            'school_year_id' => $schoolYear->id,
            'quarter' => 1,
            'name' => 'First Quarter',
            'start_date' => now()->subDays(7)->toDateString(),
            'end_date' => now()->addDays(7)->toDateString(),
            'is_locked' => false,
            'is_manually_set_active' => true,
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

        return [$schoolYear, $class];
    }

    private function enableTeacherEnrollment(): void
    {
        Setting::updateOrCreate(
            ['key' => 'teacher_enrollment'],
            ['value' => 'true']
        );
        Cache::forget('sidebar_settings');
    }

    private function disableTeacherEnrollment(): void
    {
        Setting::updateOrCreate(
            ['key' => 'teacher_enrollment'],
            ['value' => 'false']
        );
        Cache::forget('sidebar_settings');
    }
}
