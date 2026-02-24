<?php

namespace Tests\Feature\Admin;

use App\Models\Classes;
use App\Models\GradeLevel;
use App\Models\Role;
use App\Models\Schedule;
use App\Models\SchoolYear;
use App\Models\Section;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ScheduleDestroyRedirectTest extends TestCase
{
    use DatabaseTransactions;

    public function test_admin_schedule_destroy_redirects_to_manage_page_for_schedule_section(): void
    {
        $this->withoutVite();

        Role::firstOrCreate(
            ['id' => 1],
            ['name' => 'admin', 'description' => 'Administrator']
        );
        Role::firstOrCreate(
            ['id' => 2],
            ['name' => 'teacher', 'description' => 'Teacher']
        );

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
            'name' => '2099-2100-delete-redirect',
            'start_date' => now()->startOfYear()->toDateString(),
            'end_date' => now()->endOfYear()->toDateString(),
            'is_active' => true,
        ]);

        $gradeLevel = GradeLevel::create([
            'name' => 'Grade 4 Delete Redirect',
            'level' => 44,
            'description' => 'Schedule delete redirect test grade',
        ]);

        $section = Section::create([
            'name' => 'SEC-DEL-'.uniqid(),
            'grade_level_id' => $gradeLevel->id,
            'description' => 'Schedule delete redirect test section',
        ]);

        $class = Classes::create([
            'section_id' => $section->id,
            'school_year_id' => $schoolYear->id,
            'teacher_id' => $teacher->id,
            'capacity' => 40,
        ]);

        $subject = Subject::create([
            'name' => 'Subject-DEL-'.uniqid(),
            'code' => 'DEL'.uniqid(),
            'grade_level_id' => $gradeLevel->id,
            'description' => 'Schedule delete redirect test subject',
            'is_active' => true,
        ]);

        $schedule = Schedule::create([
            'class_id' => $class->id,
            'subject_id' => $subject->id,
            'teacher_id' => $teacher->id,
            'day_of_week' => ['monday'],
            'start_time' => '08:00',
            'end_time' => '09:00',
            'room' => 'Room 1',
        ]);

        $response = $this->actingAs($admin)->delete(route('admin.schedules.destroy', $schedule));

        $response->assertRedirect(route('admin.sections.manage', ['section' => $section->id]));
        $response->assertSessionHas('success', 'Assigned subject schedule deleted successfully.');
        $this->assertDatabaseMissing('schedules', ['id' => $schedule->id]);
    }
}
