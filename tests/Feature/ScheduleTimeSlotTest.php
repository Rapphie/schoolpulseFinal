<?php

namespace Tests\Feature;

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
use Illuminate\Support\Str;
use Tests\TestCase;

class ScheduleTimeSlotTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    private function ensureRole(string $name, int $id): Role
    {
        return Role::firstOrCreate(['id' => $id], ['name' => $name, 'description' => ucfirst($name)]);
    }

    /**
     * @return array{admin: User, teacher: Teacher, schoolYear: SchoolYear, gradeLevel: GradeLevel, section: Section, class: Classes, subject: Subject}
     */
    private function buildTestEnvironment(int $gradeLevelValue = 4): array
    {
        $this->ensureRole('admin', 1);
        $this->ensureRole('teacher', 2);
        SchoolYear::query()->update(['is_active' => false]);
        SchoolYearQuarter::query()->update(['is_manually_set_active' => false]);
        $suffix = Str::lower(Str::random(6));

        $schoolYear = SchoolYear::create([
            'name' => '2025-2026-schedule-test-'.$suffix,
            'start_date' => '2025-06-01',
            'end_date' => '2026-03-31',
            'is_active' => true,
        ]);

        $gradeLevel = GradeLevel::firstOrCreate(
            ['level' => $gradeLevelValue],
            ['name' => 'Grade '.$gradeLevelValue, 'description' => 'Test grade level']
        );

        $section = Section::create([
            'name' => 'SchedSec-'.$gradeLevelValue.'-'.$suffix,
            'grade_level_id' => $gradeLevel->id,
            'description' => 'Schedule test section',
        ]);

        $adminUser = User::factory()->create([
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
            'gender' => 'male',
            'date_of_birth' => '1990-01-01',
            'address' => 'Test Address',
            'qualification' => 'Bachelor of Education',
            'status' => 'active',
        ]);

        $class = Classes::create([
            'section_id' => $section->id,
            'school_year_id' => $schoolYear->id,
            'teacher_id' => $teacher->id,
            'capacity' => 40,
        ]);

        $subject = Subject::create([
            'code' => 'SCHED-TST-'.fake()->unique()->lexify('???'),
            'grade_level_id' => $gradeLevel->id,
            'name' => 'SchedTestSubj-'.fake()->unique()->word(),
            'description' => 'Test subject for schedule tests',
            'is_active' => true,
        ]);

        return compact('adminUser', 'teacher', 'teacherUser', 'schoolYear', 'gradeLevel', 'section', 'class', 'subject');
    }

    // ─── Auto-Schedule Time Reset Tests ────────────────────────────────

    public function test_assign_adviser_creates_schedules_with_zero_times(): void
    {
        $env = $this->buildTestEnvironment(1);

        $response = $this->actingAs($env['adminUser'])->post(
            route('admin.sections.adviser.assign', $env['class']),
            ['teacher_id' => $env['teacher']->id]
        );

        $response->assertRedirect();

        $schedules = Schedule::where('class_id', $env['class']->id)
            ->where('teacher_id', $env['teacher']->id)
            ->get();

        foreach ($schedules as $schedule) {
            $this->assertNotNull($schedule->start_time, 'Schedule start_time should be set after adviser auto-assignment');
            $this->assertNotNull($schedule->end_time, 'Schedule end_time should be set after adviser auto-assignment');
            $this->assertSame('00:00:00', $schedule->start_time->format('H:i:s'));
            $this->assertSame('00:00:00', $schedule->end_time->format('H:i:s'));
        }
    }

    public function test_assign_adviser_sets_schedule_zero_times_for_subject_rows(): void
    {
        $env = $this->buildTestEnvironment(2);

        $subject = Subject::firstOrCreate(
            ['code' => 'DUR-ADV-'.fake()->unique()->lexify('???')],
            [
                'grade_level_id' => $env['gradeLevel']->id,
                'name' => 'DurationAdviserSubj',
                'description' => 'Test',
            ]
        );

        $response = $this->actingAs($env['adminUser'])->post(
            route('admin.sections.adviser.assign', $env['class']),
            ['teacher_id' => $env['teacher']->id]
        );

        $response->assertRedirect();

        $schedule = Schedule::where('class_id', $env['class']->id)
            ->where('subject_id', $subject->id)
            ->first();

        $this->assertNotNull($schedule);
        $this->assertNotNull($schedule->start_time);
        $this->assertNotNull($schedule->end_time);
        $this->assertSame('00:00:00', $schedule->start_time->format('H:i:s'));
        $this->assertSame('00:00:00', $schedule->end_time->format('H:i:s'));
    }

    public function test_assign_adviser_sets_zero_times_when_subject_has_no_duration(): void
    {
        $env = $this->buildTestEnvironment(3);

        $subject = Subject::firstOrCreate(
            ['code' => 'NODUR-'.fake()->unique()->lexify('???')],
            [
                'grade_level_id' => $env['gradeLevel']->id,
                'name' => 'NoDurationSubj',
                'description' => 'Test',
            ]
        );

        $response = $this->actingAs($env['adminUser'])->post(
            route('admin.sections.adviser.assign', $env['class']),
            ['teacher_id' => $env['teacher']->id]
        );

        $response->assertRedirect();

        $schedule = Schedule::where('class_id', $env['class']->id)
            ->where('subject_id', $subject->id)
            ->first();

        $this->assertNotNull($schedule);
        $this->assertNotNull($schedule->start_time);
        $this->assertNotNull($schedule->end_time);
        $this->assertSame('00:00:00', $schedule->start_time->format('H:i:s'));
        $this->assertSame('00:00:00', $schedule->end_time->format('H:i:s'));
    }

    public function test_assign_adviser_sets_zero_times_for_multiple_schedules(): void
    {
        $env = $this->buildTestEnvironment(1);

        // Create multiple subjects for this grade level
        $subjects = [];
        foreach (['SubjA', 'SubjB', 'SubjC'] as $i => $name) {
            $subjects[] = Subject::create([
                'grade_level_id' => $env['gradeLevel']->id,
                'name' => $name.'-'.fake()->unique()->lexify('???'),
                'code' => 'STG-'.$i.'-'.fake()->unique()->lexify('???'),
                'description' => 'Stagger test subject',
            ]);
        }

        $response = $this->actingAs($env['adminUser'])->post(
            route('admin.sections.adviser.assign', $env['class']),
            ['teacher_id' => $env['teacher']->id]
        );

        $response->assertRedirect();

        $schedules = Schedule::where('class_id', $env['class']->id)
            ->where('teacher_id', $env['teacher']->id)
            ->get();

        $this->assertGreaterThanOrEqual(3, $schedules->count(), 'Should have created schedules for all subjects');

        foreach ($schedules as $schedule) {
            $this->assertNotNull($schedule->start_time);
            $this->assertNotNull($schedule->end_time);
            $this->assertSame('00:00:00', $schedule->start_time->format('H:i:s'));
            $this->assertSame('00:00:00', $schedule->end_time->format('H:i:s'));
        }
    }

    public function test_admin_manage_view_shows_not_set_for_zeroed_schedule_times(): void
    {
        $env = $this->buildTestEnvironment(1);

        Schedule::updateOrCreate(
            [
                'class_id' => $env['class']->id,
                'subject_id' => $env['subject']->id,
            ],
            [
                'teacher_id' => $env['teacher']->id,
                'day_of_week' => ['monday'],
                'start_time' => '00:00',
                'end_time' => '00:00',
                'room' => null,
            ]
        );

        $response = $this->actingAs($env['adminUser'])->get(
            route('admin.sections.manage', ['section' => $env['section'], 'class_id' => $env['class']->id])
        );

        $response->assertStatus(200);
        $response->assertSee('Not Set');
        $response->assertDontSee('12:00 AM');
    }

    public function test_teacher_view_shows_not_set_for_zeroed_schedule_times(): void
    {
        $env = $this->buildTestEnvironment(4);

        Schedule::updateOrCreate(
            [
                'class_id' => $env['class']->id,
                'subject_id' => $env['subject']->id,
            ],
            [
                'teacher_id' => $env['teacher']->id,
                'day_of_week' => ['monday'],
                'start_time' => '00:00',
                'end_time' => '00:00',
                'room' => null,
            ]
        );

        $anotherTeacherUser = User::factory()->create([
            'role_id' => 2,
            'temporary_password' => null,
        ]);

        Teacher::create([
            'user_id' => $anotherTeacherUser->id,
            'phone' => '09'.fake()->numerify('#########'),
            'gender' => 'female',
            'date_of_birth' => '1992-01-01',
            'address' => 'Test Address',
            'qualification' => 'Bachelor of Education',
            'status' => 'active',
        ]);

        $response = $this->actingAs($anotherTeacherUser)->get(route('teacher.classes.view', $env['class']));

        $response->assertStatus(200);
        $response->assertSee('Not Set');
        $response->assertDontSee('12:00 AM');
    }

    public function test_teacher_schedules_page_hides_zeroed_adviser_placeholder_schedules(): void
    {
        $env = $this->buildTestEnvironment(1);

        $this->actingAs($env['adminUser'])->post(
            route('admin.sections.adviser.assign', $env['class']),
            ['teacher_id' => $env['teacher']->id]
        )->assertRedirect();

        $response = $this->actingAs($env['teacherUser'])->get(route('teacher.schedules.index'));

        $response->assertStatus(200);
        $response->assertDontSee($env['subject']->name);
    }

    public function test_teacher_schedules_page_shows_timed_assigned_schedules(): void
    {
        $env = $this->buildTestEnvironment(4);

        Schedule::updateOrCreate(
            [
                'class_id' => $env['class']->id,
                'subject_id' => $env['subject']->id,
            ],
            [
                'teacher_id' => $env['teacher']->id,
                'day_of_week' => ['monday'],
                'start_time' => '08:00',
                'end_time' => '09:00',
                'room' => 'Room A',
            ]
        );

        $response = $this->actingAs($env['teacherUser'])->get(route('teacher.schedules.index'));

        $response->assertStatus(200);
        $response->assertSee('My Schedule');

        $this->assertDatabaseHas('schedules', [
            'class_id' => $env['class']->id,
            'subject_id' => $env['subject']->id,
            'teacher_id' => $env['teacher']->id,
        ]);
    }
}
