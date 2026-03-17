<?php

namespace Tests\Feature\Admin;

use App\Models\Classes;
use App\Models\GradeLevel;
use App\Models\Role;
use App\Models\Schedule;
use App\Models\SchoolYear;
use App\Models\SchoolYearQuarter;
use App\Models\Section;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class AdminScheduleFormRegressionTest extends TestCase
{
    use DatabaseTransactions;

    public function test_manage_edit_schedule_modal_includes_class_id_hidden_input(): void
    {
        $this->withoutVite();
        [$admin, $section, $class] = $this->createScheduleContext();

        $response = $this->actingAs($admin)
            ->get(route('admin.sections.manage', ['section' => $section]));

        $response->assertStatus(200);
        $response->assertSee('name="class_id" id="edit_class_id"', false);
        $response->assertSee('value="'.$class->id.'"', false);
    }

    public function test_admin_schedule_create_form_does_not_render_disabled_required_class_or_subject_selects(): void
    {
        $this->withoutVite();
        [$admin] = $this->createScheduleContext();

        $response = $this->actingAs($admin)->get(route('admin.schedules.create'));

        $response->assertStatus(200);
        $response->assertDontSee('id="class_id" name="class_id" required disabled', false);
        $response->assertDontSee('id="subject_id" name="subject_id" required disabled', false);
    }

    public function test_non_admin_user_gets_403_for_admin_sections_index(): void
    {
        $this->withoutVite();
        $this->createRole(1, 'admin');
        $this->createRole(2, 'teacher');

        $teacherUser = User::factory()->create([
            'role_id' => 2,
            'temporary_password' => null,
        ]);
        /** @var User $teacherUser */
        $response = $this->actingAs($teacherUser)->get(route('admin.sections.index'));

        $response->assertForbidden();
    }

    /**
     * @return array{0: User, 1: Section, 2: Classes}
     */
    private function createScheduleContext(): array
    {
        $this->createRole(1, 'admin');
        $this->createRole(2, 'teacher');

        SchoolYearQuarter::query()->update(['is_manually_set_active' => false]);
        SchoolYear::query()->update(['is_active' => false]);

        $admin = User::factory()->create([
            'role_id' => 1,
            'temporary_password' => null,
        ]);

        $teacherUser = User::factory()->create([
            'role_id' => 2,
            'temporary_password' => null,
        ]);

        $teacher = Teacher::create([
            'user_id' => $teacherUser->id,
            'phone' => '09'.fake()->numerify('#########'),
            'gender' => 'female',
            'date_of_birth' => '1990-01-01',
            'address' => 'Test Address',
            'qualification' => 'Bachelor of Education',
            'status' => 'active',
        ]);

        $schoolYear = SchoolYear::create([
            'name' => '2099-2100-schedule-regression',
            'start_date' => now()->startOfYear()->toDateString(),
            'end_date' => now()->endOfYear()->toDateString(),
            'is_active' => true,
        ]);

        $gradeLevel = GradeLevel::create([
            'name' => 'Grade 4 Schedule Regression',
            'level' => 45,
            'description' => 'Schedule regression test grade',
        ]);

        $section = Section::create([
            'name' => 'SEC-REG-'.uniqid(),
            'grade_level_id' => $gradeLevel->id,
            'description' => 'Schedule regression test section',
        ]);

        $class = Classes::create([
            'section_id' => $section->id,
            'school_year_id' => $schoolYear->id,
            'teacher_id' => $teacher->id,
            'capacity' => 40,
        ]);

        $subject = Subject::create([
            'name' => 'Subject-REG-'.uniqid(),
            'code' => 'REG'.uniqid(),
            'grade_level_id' => $gradeLevel->id,
            'description' => 'Schedule regression test subject',
            'is_active' => true,
        ]);

        Schedule::create([
            'class_id' => $class->id,
            'subject_id' => $subject->id,
            'teacher_id' => $teacher->id,
            'day_of_week' => ['monday'],
            'start_time' => '08:00',
            'end_time' => '09:00',
            'room' => 'Room 1',
        ]);

        return [$admin, $section, $class];
    }

    private function createRole(int $id, string $name): void
    {
        Role::firstOrCreate(
            ['id' => $id],
            ['name' => $name, 'description' => ucfirst($name)]
        );
    }
}
