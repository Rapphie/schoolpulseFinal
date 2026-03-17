<?php

namespace Tests\Feature\Guardian;

use App\Livewire\Guardian\Dashboard;
use App\Livewire\Guardian\StudentAttendance;
use App\Livewire\Guardian\StudentGrades;
use App\Models\Attendance;
use App\Models\Classes;
use App\Models\Enrollment;
use App\Models\Grade;
use App\Models\GradeLevel;
use App\Models\Guardian;
use App\Models\Role;
use App\Models\SchoolYear;
use App\Models\SchoolYearQuarter;
use App\Models\Section;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class GuardianDashboardTest extends TestCase
{
    use DatabaseTransactions;

    private User $guardianUser;

    private Guardian $guardian;

    private User $adminUser;

    private Teacher $teacher;

    private SchoolYear $schoolYear;

    private Classes $class;

    private Subject $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seedEnvironment();
    }

    public function test_guardian_with_no_students_sees_no_linked_students_message(): void
    {
        $this->actingAs($this->guardianUser)
            ->get(route('guardian.dashboard'))
            ->assertStatus(200)
            ->assertSee('No linked students yet.');
    }

    public function test_guardian_with_one_student_sees_student_information_without_selector_dropdown(): void
    {
        $student = $this->createLinkedStudent('Ariel', 'Solo');

        $this->actingAs($this->guardianUser)
            ->get(route('guardian.dashboard'))
            ->assertStatus(200)
            ->assertSee($student->full_name)
            ->assertSee($student->student_id)
            ->assertDontSee('wire:model.live="selectedStudentId"', false);
    }

    public function test_guardian_with_multiple_students_sees_selector_and_can_switch_students(): void
    {
        $studentA = $this->createLinkedStudent('Alpha', 'One');
        $studentB = $this->createLinkedStudent('Beta', 'Two');

        $this->actingAs($this->guardianUser)
            ->get(route('guardian.dashboard'))
            ->assertStatus(200)
            ->assertSee('wire:model.live="selectedStudentId"', false)
            ->assertSee($studentA->full_name)
            ->assertSee($studentB->full_name);

        Livewire::actingAs($this->guardianUser)
            ->test(Dashboard::class)
            ->assertSet('selectedStudentId', $studentA->id)
            ->call('selectStudent', $studentB->id)
            ->assertSet('selectedStudentId', $studentB->id)
            ->assertSee($studentB->student_id);

        $this->assertSame(
            $studentB->id,
            session()->get('guardian.selected_student_id.'.$this->guardianUser->id)
        );
    }

    public function test_selected_student_persists_in_session_across_guardian_pages(): void
    {
        $this->createLinkedStudent('Alpha', 'Persist');
        $studentB = $this->createLinkedStudent('Beta', 'Persist');

        Livewire::actingAs($this->guardianUser)
            ->test(Dashboard::class)
            ->call('selectStudent', $studentB->id)
            ->assertSet('selectedStudentId', $studentB->id);

        $this->actingAs($this->guardianUser)
            ->get(route('guardian.grades'))
            ->assertStatus(200)
            ->assertSee($studentB->student_id);

        Livewire::actingAs($this->guardianUser)
            ->test(StudentGrades::class)
            ->assertSet('selectedStudentId', $studentB->id);

        $this->actingAs($this->guardianUser)
            ->get(route('guardian.attendance'))
            ->assertStatus(200)
            ->assertSee($studentB->student_id);
    }

    public function test_guardian_cannot_select_another_guardians_student(): void
    {
        $this->createLinkedStudent('Owned', 'Student');
        $otherGuardian = $this->createOtherGuardian('Intruder');
        $otherStudent = $this->createLinkedStudent('Outside', 'Student', $otherGuardian);

        Livewire::actingAs($this->guardianUser)
            ->test(Dashboard::class)
            ->set('selectedStudentId', $otherStudent->id)
            ->assertForbidden();
    }

    public function test_grades_display_correct_values_by_quarter(): void
    {
        $student = $this->createLinkedStudent('Grade', 'Target');

        Grade::create([
            'student_id' => $student->id,
            'subject_id' => $this->subject->id,
            'teacher_id' => $this->teacher->id,
            'school_year_id' => $this->schoolYear->id,
            'grade' => 88,
            'quarter' => '1',
            'quarter_int' => 1,
        ]);

        Grade::create([
            'student_id' => $student->id,
            'subject_id' => $this->subject->id,
            'teacher_id' => $this->teacher->id,
            'school_year_id' => $this->schoolYear->id,
            'grade' => 92,
            'quarter' => '2',
            'quarter_int' => 2,
        ]);

        Livewire::actingAs($this->guardianUser)
            ->test(StudentGrades::class)
            ->assertSet('selectedQuarter', 1)
            ->assertSee('88')
            ->assertSee('Passed')
            ->call('setQuarter', 2)
            ->assertSet('selectedQuarter', 2)
            ->assertSee('92');
    }

    public function test_attendance_display_summary_counts_for_selected_student(): void
    {
        $student = $this->createLinkedStudent('Attendance', 'Target');
        $this->createAttendance($student, 'present', now()->subDays(5)->toDateString(), 1);
        $this->createAttendance($student, 'present', now()->subDays(4)->toDateString(), 1);
        $this->createAttendance($student, 'absent', now()->subDays(3)->toDateString(), 1);
        $this->createAttendance($student, 'late', now()->subDays(2)->toDateString(), 1);
        $this->createAttendance($student, 'excused', now()->subDay()->toDateString(), 1);

        Livewire::actingAs($this->guardianUser)
            ->test(StudentAttendance::class)
            ->assertSee('Present')
            ->assertSee('Absent')
            ->assertSee('Late')
            ->assertSee('Excused')
            ->assertSee('40.00%');
    }

    public function test_guardian_pages_load_and_return_ok_status(): void
    {
        $this->createLinkedStudent('Route', 'Check');

        $this->actingAs($this->guardianUser)
            ->get(route('guardian.dashboard'))
            ->assertStatus(200);

        $this->actingAs($this->guardianUser)
            ->get(route('guardian.grades'))
            ->assertStatus(200);

        $this->actingAs($this->guardianUser)
            ->get(route('guardian.attendance'))
            ->assertStatus(200);
    }

    private function seedEnvironment(): void
    {
        $suffix = Str::lower(Str::random(6));
        SchoolYear::query()->update(['is_active' => false]);
        SchoolYearQuarter::query()->update(['is_manually_set_active' => false]);

        $this->ensureRole('admin', 1);
        $this->ensureRole('teacher', 2);
        $this->ensureRole('guardian', 3);

        $this->adminUser = User::factory()->create([
            'role_id' => 1,
            'temporary_password' => null,
        ]);

        $teacherUser = User::factory()->create([
            'role_id' => 2,
            'temporary_password' => null,
        ]);

        $this->guardianUser = User::factory()->create([
            'role_id' => 3,
            'temporary_password' => null,
        ]);

        $this->guardian = Guardian::create([
            'user_id' => $this->guardianUser->id,
            'phone' => '09'.fake()->numerify('#########'),
            'relationship' => 'parent',
        ]);

        $this->teacher = Teacher::create([
            'user_id' => $teacherUser->id,
            'phone' => '09'.fake()->numerify('#########'),
            'gender' => 'male',
            'date_of_birth' => '1990-01-01',
            'address' => 'Teacher Address',
            'qualification' => 'Bachelor of Education',
            'status' => 'active',
        ]);

        $this->schoolYear = SchoolYear::create([
            'name' => '2099-2100-'.$suffix,
            'start_date' => now()->startOfYear()->toDateString(),
            'end_date' => now()->endOfYear()->toDateString(),
            'is_active' => true,
            'is_promotion_open' => true,
        ]);

        $gradeLevel = GradeLevel::create([
            'name' => 'Grade 4 '.$suffix,
            'level' => random_int(10, 99),
            'description' => 'Guardian dashboard test',
        ]);

        $section = Section::create([
            'name' => 'Section-'.$suffix,
            'grade_level_id' => $gradeLevel->id,
            'description' => 'Guardian test section',
        ]);

        $this->class = Classes::create([
            'section_id' => $section->id,
            'school_year_id' => $this->schoolYear->id,
            'teacher_id' => $this->teacher->id,
            'capacity' => 40,
        ]);

        $this->subject = Subject::create([
            'grade_level_id' => $gradeLevel->id,
            'name' => 'Mathematics '.$suffix,
            'code' => 'MTH'.strtoupper(Str::random(5)),
            'description' => 'Guardian test subject',
            'is_active' => true,
        ]);
    }

    private function createLinkedStudent(string $firstName, string $lastName, ?Guardian $guardian = null): Student
    {
        $student = Student::create([
            'student_id' => strtoupper('STD'.Str::random(8)),
            'lrn' => fake()->unique()->numerify('############'),
            'first_name' => $firstName,
            'last_name' => $lastName,
            'gender' => 'male',
            'birthdate' => '2014-01-01',
            'address' => 'Student Address',
            'guardian_id' => ($guardian ?? $this->guardian)->id,
            'enrollment_date' => now()->toDateString(),
        ]);

        Enrollment::create([
            'student_id' => $student->id,
            'class_id' => $this->class->id,
            'school_year_id' => $this->schoolYear->id,
            'teacher_id' => $this->teacher->id,
            'enrolled_by_user_id' => $this->adminUser->id,
            'student_profile_id' => null,
            'enrollment_date' => now()->toDateString(),
            'status' => 'enrolled',
        ]);

        return $student;
    }

    private function createOtherGuardian(string $suffix): Guardian
    {
        $otherGuardianUser = User::factory()->create([
            'role_id' => 3,
            'email' => 'guardian-'.$suffix.'@example.com',
            'temporary_password' => null,
        ]);

        return Guardian::create([
            'user_id' => $otherGuardianUser->id,
            'phone' => '09'.fake()->numerify('#########'),
            'relationship' => 'guardian',
        ]);
    }

    private function createAttendance(Student $student, string $status, string $date, int $quarter): Attendance
    {
        return Attendance::create([
            'student_id' => $student->id,
            'class_id' => $this->class->id,
            'subject_id' => $this->subject->id,
            'teacher_id' => $this->teacher->id,
            'school_year_id' => $this->schoolYear->id,
            'student_profile_id' => null,
            'time_in' => '08:00:00',
            'status' => $status,
            'date' => $date,
            'quarter' => (string) $quarter,
            'quarter_int' => $quarter,
        ]);
    }

    private function ensureRole(string $name, int $id): void
    {
        Role::firstOrCreate(
            ['id' => $id],
            ['name' => $name, 'description' => ucfirst($name)]
        );
    }
}
