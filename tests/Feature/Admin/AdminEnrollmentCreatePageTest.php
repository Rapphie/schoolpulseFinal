<?php

// Tests for Admin Enrollment

namespace Tests\Feature\Admin;

use App\Models\Classes;
use App\Models\Enrollment;
use App\Models\GradeLevel;
use App\Models\Guardian;
use App\Models\Role;
use App\Models\SchoolYear;
use App\Models\Section;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdminEnrollmentCreatePageTest extends TestCase
{
    use DatabaseTransactions;

    public function test_admin_can_view_enrollment_create_page(): void
    {
        $admin = $this->createAdminUser();
        [$schoolYear, $class] = $this->createActiveClass();

        $response = $this->actingAs($admin)
            ->get(route('admin.enrollment.create', $class));

        $response->assertStatus(200);
        $response->assertSee('Enroll New Student');
        $response->assertSee($class->section->name);
    }

    public function test_enrollment_create_page_shows_capacity_info(): void
    {
        $admin = $this->createAdminUser();
        [$schoolYear, $class] = $this->createActiveClass();

        $response = $this->actingAs($admin)
            ->get(route('admin.enrollment.create', $class));

        $response->assertStatus(200);
        $response->assertSee('0 / 40');
    }

    public function test_guest_cannot_access_enrollment_create_page(): void
    {
        [$schoolYear, $class] = $this->createActiveClass();

        $response = $this->get(route('admin.enrollment.create', $class));

        $response->assertRedirect(route('login'));
    }

    public function test_admin_can_enroll_student_from_create_page(): void
    {
        Mail::fake();

        $admin = $this->createAdminUser();
        [$schoolYear, $class] = $this->createActiveClass();

        $response = $this->actingAs($admin)
            ->post(route('admin.enrollment.store', $class), [
                'first_name' => 'Juan',
                'last_name' => 'Dela Cruz',
                'gender' => 'male',
                'birthdate' => '2016-03-15',
                'address' => '123 Test Street',
                'guardian_first_name' => 'Maria',
                'guardian_last_name' => 'Dela Cruz',
                'guardian_email' => 'guardian.'.Str::lower(Str::random(8)).'@example.com',
                'guardian_phone' => '09171234567',
                'guardian_relationship' => 'parent',
            ]);

        $response->assertRedirect(route('admin.sections.manage', $class->section));
        $response->assertSessionHas('success', 'Student enrolled successfully.');
    }

    public function test_admin_can_enroll_multiple_students_without_guardian_email(): void
    {
        Mail::fake();

        $admin = $this->createAdminUser();
        [$schoolYear, $class] = $this->createActiveClass();

        $firstResponse = $this->actingAs($admin)
            ->post(route('admin.enrollment.store', $class), [
                'first_name' => 'First',
                'last_name' => 'NoEmail',
                'gender' => 'male',
                'birthdate' => '2016-03-15',
                'address' => '123 Test Street',
                'guardian_first_name' => 'Maria',
                'guardian_last_name' => 'Guardian',
                'guardian_email' => '',
                'guardian_phone' => '',
                'guardian_relationship' => 'parent',
            ]);

        $firstResponse->assertRedirect(route('admin.sections.manage', $class->section));
        $firstResponse->assertSessionHas('success', 'Student enrolled successfully.');

        $secondResponse = $this->actingAs($admin)
            ->post(route('admin.enrollment.store', $class), [
                'first_name' => 'Second',
                'last_name' => 'NoEmail',
                'gender' => 'female',
                'birthdate' => '2016-03-16',
                'address' => '456 Test Street',
                'guardian_first_name' => 'Nora',
                'guardian_last_name' => 'Guardian',
                'guardian_email' => '',
                'guardian_phone' => '',
                'guardian_relationship' => 'guardian',
            ]);

        $secondResponse->assertRedirect(route('admin.sections.manage', $class->section));
        $secondResponse->assertSessionHas('success', 'Student enrolled successfully.');
        $this->assertSame(2, User::query()->whereNull('email')->count());
        Mail::assertNothingQueued();
    }

    public function test_admin_can_search_existing_guardians_for_dropdown(): void
    {
        $admin = $this->createAdminUser();
        [$schoolYear, $class] = $this->createActiveClass();

        $guardianRole = Role::firstOrCreate(
            ['name' => 'guardian'],
            ['description' => 'Guardian role']
        );
        $guardianUser = User::factory()->create([
            'role_id' => $guardianRole->id,
            'email' => 'existing.guardian.'.Str::lower(Str::random(8)).'@example.com',
        ]);
        $guardian = Guardian::create([
            'user_id' => $guardianUser->id,
            'phone' => '09179990000',
            'relationship' => 'parent',
        ]);
        $student = Student::create([
            'student_id' => Student::generateStudentId($schoolYear),
            'first_name' => 'Linked',
            'last_name' => 'Child',
            'gender' => 'male',
            'birthdate' => '2016-01-01',
            'guardian_id' => $guardian->id,
            'enrollment_date' => now(),
        ]);

        $response = $this->actingAs($admin)
            ->getJson(route('admin.enrollment.guardian.search', ['q' => 'existing.guardian']));

        $response->assertOk();
        $response->assertJsonCount(1, 'guardians');
        $response->assertJsonPath('guardians.0.id', $guardian->id);
        $response->assertJsonPath('guardians.0.email', $guardianUser->email);
        $response->assertJsonPath('guardians.0.connected_student.first_name', $student->first_name);
        $response->assertJsonPath('guardians.0.connected_student.last_name', $student->last_name);
    }

    public function test_admin_guardian_search_returns_empty_for_unknown_keyword(): void
    {
        $admin = $this->createAdminUser();
        [$schoolYear, $class] = $this->createActiveClass();

        $response = $this->actingAs($admin)
            ->getJson(route('admin.enrollment.guardian.search', ['q' => 'missing.guardian@example.com']));

        $response->assertOk();
        $response->assertJsonCount(0, 'guardians');
    }

    public function test_admin_can_enroll_student_using_existing_guardian_credentials(): void
    {
        Mail::fake();

        $admin = $this->createAdminUser();
        [$schoolYear, $class] = $this->createActiveClass();

        $guardianRole = Role::firstOrCreate(
            ['name' => 'guardian'],
            ['description' => 'Guardian role']
        );
        $guardianUser = User::factory()->create([
            'role_id' => $guardianRole->id,
            'email' => 'shared.guardian.'.Str::lower(Str::random(8)).'@example.com',
            'first_name' => 'Shared',
            'last_name' => 'Guardian',
        ]);
        $guardian = Guardian::create([
            'user_id' => $guardianUser->id,
            'phone' => '09178887777',
            'relationship' => 'parent',
        ]);
        Student::create([
            'student_id' => Student::generateStudentId($schoolYear),
            'first_name' => 'First',
            'last_name' => 'Child',
            'gender' => 'female',
            'birthdate' => '2016-02-15',
            'guardian_id' => $guardian->id,
            'enrollment_date' => now(),
        ]);

        $userCountBefore = User::count();
        $guardianCountBefore = Guardian::count();

        $response = $this->actingAs($admin)
            ->post(route('admin.enrollment.store', $class), [
                'use_existing_guardian' => 1,
                'guardian_id' => $guardian->id,
                'first_name' => 'Second',
                'last_name' => 'Child',
                'gender' => 'male',
                'birthdate' => '2016-03-15',
                'address' => '123 Test Street',
                'guardian_first_name' => 'Shared',
                'guardian_last_name' => 'Guardian',
                'guardian_email' => $guardianUser->email,
                'guardian_phone' => '09178887777',
                'guardian_relationship' => 'parent',
            ]);

        $response->assertRedirect(route('admin.sections.manage', $class->section));
        $response->assertSessionHas('success', function (string $message) {
            return str_contains($message, 'existing credentials were used');
        });

        $this->assertDatabaseHas('students', [
            'first_name' => 'Second',
            'last_name' => 'Child',
            'guardian_id' => $guardian->id,
        ]);
        $this->assertSame($userCountBefore, User::count());
        $this->assertSame($guardianCountBefore, Guardian::count());
        Mail::assertNothingSent();
    }

    public function test_admin_enrollment_sets_enrollment_date_to_current_date(): void
    {
        Mail::fake();
        Carbon::setTestNow(Carbon::parse('2031-09-17 10:00:00'));

        try {
            $admin = $this->createAdminUser();
            [$schoolYear, $class] = $this->createActiveClass();

            $response = $this->actingAs($admin)
                ->post(route('admin.enrollment.store', $class), [
                    'first_name' => 'Date',
                    'last_name' => 'Check',
                    'gender' => 'male',
                    'birthdate' => '2016-03-15',
                    'address' => '123 Test Street',
                    'guardian_first_name' => 'Maria',
                    'guardian_last_name' => 'Dela Cruz',
                    'guardian_email' => 'guardian.'.Str::lower(Str::random(8)).'@example.com',
                    'guardian_phone' => '09171234567',
                    'guardian_relationship' => 'parent',
                ]);

            $response->assertRedirect(route('admin.sections.manage', $class->section));

            $enrollment = Enrollment::query()
                ->where('class_id', $class->id)
                ->latest('id')
                ->first();

            $this->assertNotNull($enrollment);
            $this->assertSame('2031-09-17', $enrollment->enrollment_date?->toDateString());
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_enroll_student_validates_required_fields(): void
    {
        Mail::fake();

        $admin = $this->createAdminUser();
        [$schoolYear, $class] = $this->createActiveClass();

        $response = $this->actingAs($admin)
            ->post(route('admin.enrollment.store', $class), []);

        $response->assertSessionHasErrors([
            'first_name',
            'last_name',
            'gender',
            'birthdate',
            'guardian_first_name',
            'guardian_last_name',
            'guardian_relationship',
        ]);
    }

    public function test_enroll_student_rejects_over_capacity(): void
    {
        Mail::fake();

        $admin = $this->createAdminUser();
        [$schoolYear, $class] = $this->createActiveClass();
        $class->update(['capacity' => 0]);

        $response = $this->actingAs($admin)
            ->post(route('admin.enrollment.store', $class), [
                'first_name' => 'Over',
                'last_name' => 'Capacity',
                'gender' => 'female',
                'birthdate' => '2016-06-10',
                'guardian_first_name' => 'Guardian',
                'guardian_last_name' => 'Over',
                'guardian_email' => 'over.'.Str::lower(Str::random(8)).'@example.com',
                'guardian_phone' => '09181234567',
                'guardian_relationship' => 'parent',
            ]);

        $response->assertSessionHas('error', 'This class has reached its full capacity.');
    }

    public function test_admin_can_clear_guardian_contact_details_when_updating_student(): void
    {
        $admin = $this->createAdminUser();
        $guardianRole = Role::firstOrCreate(
            ['name' => 'guardian'],
            ['description' => 'Guardian role']
        );
        $guardianUser = User::factory()->create([
            'role_id' => $guardianRole->id,
            'email' => 'clear.guardian.'.Str::lower(Str::random(8)).'@example.com',
            'first_name' => 'Clear',
            'last_name' => 'Guardian',
        ]);
        $guardian = Guardian::create([
            'user_id' => $guardianUser->id,
            'phone' => '09175550000',
            'relationship' => 'parent',
        ]);
        $student = Student::create([
            'student_id' => Student::generateStudentId(),
            'first_name' => 'Update',
            'last_name' => 'Student',
            'gender' => 'female',
            'birthdate' => '2016-03-15',
            'guardian_id' => $guardian->id,
            'enrollment_date' => now(),
        ]);

        $response = $this->actingAs($admin)
            ->from(route('admin.students.edit', $student))
            ->put(route('admin.students.update', $student), [
                'lrn' => '',
                'first_name' => 'Update',
                'last_name' => 'Student',
                'gender' => 'female',
                'birthdate' => '2016-03-15',
                'address' => '',
                'distance_km' => '',
                'transportation' => '',
                'family_income' => '',
                'guardian_first_name' => 'Clear',
                'guardian_last_name' => 'Guardian',
                'guardian_email' => '',
                'guardian_phone' => '',
                'guardian_relationship' => 'parent',
            ]);

        $response->assertRedirect(route('admin.students.edit', $student));
        $response->assertSessionHas('success', 'Student updated successfully.');

        $guardianUser->refresh();
        $guardian->refresh();

        $this->assertNull($guardianUser->email);
        $this->assertNull($guardian->phone);
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
    private function createActiveClass(): array
    {
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
            'teacher_id' => null,
            'capacity' => 40,
        ]);

        return [$schoolYear, $class];
    }
}
