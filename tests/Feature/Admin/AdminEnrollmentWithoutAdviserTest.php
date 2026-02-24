<?php

namespace Tests\Feature\Admin;

use App\Models\Classes;
use App\Models\Enrollment;
use App\Models\GradeLevel;
use App\Models\Role;
use App\Models\SchoolYear;
use App\Models\Section;
use App\Models\Student;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdminEnrollmentWithoutAdviserTest extends TestCase
{
    use DatabaseTransactions;

    public function test_admin_can_enroll_new_student_even_when_class_has_no_assigned_adviser(): void
    {
        Mail::fake();

        $admin = $this->createAdminUser();
        [$schoolYear, $class] = $this->createOpenClassWithoutAdviser();

        $response = $this->actingAs($admin)
            ->from(route('admin.enrollment.index', ['school_year_id' => $schoolYear->id]))
            ->post(route('admin.enrollment.page.store'), [
                'class_id' => $class->id,
                'lrn' => '202602130001',
                'first_name' => 'NoAdviser',
                'last_name' => 'NewStudent',
                'gender' => 'male',
                'birthdate' => '2016-01-20',
                'address' => 'Test Address',
                'guardian_first_name' => 'Guardian',
                'guardian_last_name' => 'One',
                'guardian_email' => 'guardian.new.'.Str::lower(Str::random(8)).'@example.com',
                'guardian_phone' => '09123456789',
                'guardian_relationship' => 'parent',
                'enrollment_status' => 'enrolled',
            ]);

        $response->assertRedirect(route('admin.enrollment.index', ['school_year_id' => $schoolYear->id]));
        $response->assertSessionHas('success');

        $enrollment = Enrollment::query()
            ->where('class_id', $class->id)
            ->latest('id')
            ->first();

        $this->assertNotNull($enrollment);
        $this->assertNull($enrollment->teacher_id);
        $this->assertSame($admin->id, $enrollment->enrolled_by_user_id);
        $this->assertSame($schoolYear->id, $enrollment->school_year_id);
    }

    public function test_admin_can_reenroll_past_student_even_when_class_has_no_assigned_adviser(): void
    {
        $admin = $this->createAdminUser();
        [$schoolYear, $class] = $this->createOpenClassWithoutAdviser();
        $schoolYear->update(['is_promotion_open' => true]);

        $student = Student::create([
            'student_id' => Student::generateStudentId(),
            'first_name' => 'Past',
            'last_name' => 'Student',
            'gender' => 'female',
            'birthdate' => '2015-05-11',
            'enrollment_date' => now(),
        ]);

        $response = $this->actingAs($admin)
            ->post(route('admin.enrollment.page.storePastStudent'), [
                'student_id' => (string) $student->id,
                'class_id' => $class->id,
                'enrollment_status' => 'enrolled',
            ]);

        $response->assertRedirect(route('admin.enrollment.index', ['school_year_id' => $schoolYear->id]));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('enrollments', [
            'student_id' => $student->id,
            'class_id' => $class->id,
            'school_year_id' => $schoolYear->id,
            'teacher_id' => null,
            'enrolled_by_user_id' => $admin->id,
            'status' => 'enrolled',
        ]);
    }

    public function test_admin_store_past_student_pending_profile_enrollment_succeeds_for_active_school_year_when_promotion_closed(): void
    {
        $admin = $this->createAdminUser();
        [$schoolYear, $class] = $this->createOpenClassWithoutAdviser();

        $student = Student::create([
            'student_id' => Student::generateStudentId(),
            'first_name' => 'Pending',
            'last_name' => 'Admin',
            'gender' => 'female',
            'birthdate' => '2015-02-10',
            'enrollment_date' => now(),
        ]);

        $class->load('section');
        $profile = StudentProfile::create([
            'student_id' => $student->id,
            'school_year_id' => $schoolYear->id,
            'grade_level_id' => $class->section->grade_level_id,
            'status' => 'pending',
        ]);

        $this->assertSame('pending', $profile->status);

        $response = $this->actingAs($admin)
            ->post(route('admin.enrollment.page.storePastStudent'), [
                'student_type' => 'enroll',
                'student_id' => (string) $student->id,
                'class_id' => $class->id,
                'enrollment_status' => 'enrolled',
            ]);

        $response->assertRedirect(route('admin.enrollment.index', ['school_year_id' => $schoolYear->id]));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('enrollments', [
            'student_id' => $student->id,
            'class_id' => $class->id,
            'school_year_id' => $schoolYear->id,
            'teacher_id' => null,
            'enrolled_by_user_id' => $admin->id,
            'status' => 'enrolled',
        ]);

        $profile->refresh();
        $this->assertSame('enrolled', $profile->status);
    }

    public function test_admin_store_error_flashes_old_input_for_wizard_rehydration(): void
    {
        $admin = $this->createAdminUser();
        [$activeSchoolYear] = $this->createOpenClassWithoutAdviser();

        $closedSchoolYear = SchoolYear::create([
            'name' => 'SY-'.Str::upper(Str::random(6)),
            'start_date' => now()->subYears(2)->startOfMonth()->toDateString(),
            'end_date' => now()->subYear()->endOfMonth()->toDateString(),
            'is_active' => false,
            'is_promotion_open' => false,
        ]);
        $closedClass = $this->createClassWithoutAdviser($closedSchoolYear);

        $response = $this->actingAs($admin)
            ->from(route('admin.enrollment.index', ['school_year_id' => $closedSchoolYear->id]))
            ->post(route('admin.enrollment.page.store'), [
                'student_type' => 'new',
                'class_id' => $closedClass->id,
                'lrn' => '202602130101',
                'first_name' => 'Wizard',
                'last_name' => 'Restore',
                'gender' => 'male',
                'birthdate' => '2016-02-01',
                'address' => 'Old Input Address',
                'guardian_first_name' => 'Guardian',
                'guardian_last_name' => 'Restore',
                'guardian_email' => 'wizard.restore.'.Str::lower(Str::random(8)).'@example.com',
                'guardian_phone' => '09120000001',
                'guardian_relationship' => 'parent',
                'enrollment_status' => 'transferred',
            ]);

        $response->assertRedirect(route('admin.enrollment.index', ['school_year_id' => $closedSchoolYear->id]));
        $response->assertSessionHas('error');
        $response->assertSessionHasInput('student_type', 'new');
        $response->assertSessionHasInput('class_id', (string) $closedClass->id);
        $response->assertSessionHasInput('first_name', 'Wizard');
        $response->assertSessionHasInput('enrollment_status', 'transferred');
        $this->assertTrue($activeSchoolYear->is_active);
    }

    public function test_profile_validation_error_sets_profile_tab_hint_for_admin_enrollment_wizard(): void
    {
        $admin = $this->createAdminUser();
        [$schoolYear, $class] = $this->createOpenClassWithoutAdviser();

        $response = $this->actingAs($admin)
            ->from(route('admin.enrollment.index', ['school_year_id' => $schoolYear->id]))
            ->post(route('admin.enrollment.page.store'), [
                'student_type' => 'new',
                'class_id' => $class->id,
                'lrn' => '202602130777',
                'first_name' => '',
                'last_name' => 'Validation',
                'gender' => 'male',
                'birthdate' => '2016-02-01',
                'address' => 'Old Input Address',
                'guardian_first_name' => 'Guardian',
                'guardian_last_name' => 'Validation',
                'guardian_email' => 'wizard.validation.'.Str::lower(Str::random(8)).'@example.com',
                'guardian_phone' => '09120000001',
                'guardian_relationship' => 'parent',
                'enrollment_status' => 'enrolled',
            ]);

        $response->assertRedirect(route('admin.enrollment.index', ['school_year_id' => $schoolYear->id]));
        $response->assertSessionHasErrors(['first_name']);
        $response->assertSessionHasInput('student_type', 'new');
        $response->assertSessionHasInput('class_id', (string) $class->id);

        $hydratedWizardResponse = $this->followRedirects($response);
        $hydratedWizardResponse->assertSee('"hasProfileErrors":true', false);
        $hydratedWizardResponse->assertSee('"profileFieldsWithErrors":["first_name"]', false);
    }

    public function test_admin_store_past_student_error_flashes_old_input_for_wizard_rehydration(): void
    {
        $admin = $this->createAdminUser();
        [$schoolYear, $class] = $this->createOpenClassWithoutAdviser();
        $class->update(['capacity' => 0]);

        $student = Student::create([
            'student_id' => Student::generateStudentId(),
            'first_name' => 'Old',
            'last_name' => 'Input',
            'gender' => 'female',
            'birthdate' => '2016-03-15',
            'enrollment_date' => now(),
        ]);

        $response = $this->actingAs($admin)
            ->from(route('admin.enrollment.index', ['school_year_id' => $schoolYear->id]))
            ->post(route('admin.enrollment.page.storePastStudent'), [
                'student_type' => 'returning',
                'student_id' => (string) $student->id,
                'class_id' => $class->id,
                'enrollment_status' => 'enrolled',
            ]);

        $response->assertRedirect(route('admin.enrollment.index', ['school_year_id' => $schoolYear->id]));
        $response->assertSessionHas('error');
        $response->assertSessionHasInput('student_type', 'returning');
        $response->assertSessionHasInput('student_id', (string) $student->id);
        $response->assertSessionHasInput('class_id', (string) $class->id);
        $response->assertSessionHasInput('enrollment_status', 'enrolled');
    }

    private function createAdminUser(): User
    {
        $adminRole = Role::firstOrCreate(
            ['name' => 'admin'],
            ['description' => 'Administrator role']
        );

        return User::factory()->create([
            'role_id' => $adminRole->id,
            'password' => Hash::make('password'),
        ]);
    }

    /**
     * @return array{SchoolYear, Classes}
     */
    private function createOpenClassWithoutAdviser(): array
    {
        $schoolYear = SchoolYear::create([
            'name' => 'SY-'.Str::upper(Str::random(6)),
            'start_date' => now()->startOfMonth()->toDateString(),
            'end_date' => now()->addMonths(10)->endOfMonth()->toDateString(),
            'is_active' => true,
            'is_promotion_open' => false,
        ]);

        $class = $this->createClassWithoutAdviser($schoolYear);

        return [$schoolYear, $class];
    }

    private function createClassWithoutAdviser(SchoolYear $schoolYear): Classes
    {
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

        return Classes::create([
            'section_id' => $section->id,
            'school_year_id' => $schoolYear->id,
            'teacher_id' => null,
            'capacity' => 40,
        ]);
    }
}
