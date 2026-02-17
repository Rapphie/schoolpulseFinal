<?php

// Tests for Admin Enrollment

namespace Tests\Feature\Admin;

use App\Models\Classes;
use App\Models\Enrollment;
use App\Models\GradeLevel;
use App\Models\Role;
use App\Models\SchoolYear;
use App\Models\Section;
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
            'guardian_email',
            'guardian_phone',
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
