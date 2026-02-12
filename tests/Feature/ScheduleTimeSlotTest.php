<?php

namespace Tests\Feature;

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

        $schoolYear = SchoolYear::firstOrCreate(
            ['name' => '2025-2026-schedule-test'],
            ['start_date' => '2025-06-01', 'end_date' => '2026-03-31', 'is_active' => true]
        );

        $gradeLevel = GradeLevel::firstOrCreate(
            ['level' => $gradeLevelValue],
            ['name' => 'Grade '.$gradeLevelValue, 'description' => 'Test grade level']
        );

        $section = Section::firstOrCreate(
            ['name' => 'SchedSec-'.$gradeLevelValue, 'grade_level_id' => $gradeLevel->id],
            ['description' => 'Schedule test section']
        );

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

        $class = Classes::firstOrCreate(
            ['section_id' => $section->id, 'school_year_id' => $schoolYear->id],
            ['teacher_id' => $teacher->id, 'capacity' => 40]
        );

        $subject = Subject::firstOrCreate(
            ['code' => 'SCHED-TST-'.fake()->unique()->lexify('???')],
            [
                'grade_level_id' => $gradeLevel->id,
                'name' => 'SchedTestSubj-'.fake()->unique()->word(),
                'description' => 'Test subject for schedule tests',
                'duration_minutes' => 60,
            ]
        );

        return compact('adminUser', 'teacher', 'teacherUser', 'schoolYear', 'gradeLevel', 'section', 'class', 'subject');
    }

    // ─── Subject Duration Tests ────────────────────────────────────────

    public function test_subject_store_accepts_duration_minutes(): void
    {
        $env = $this->buildTestEnvironment();

        $response = $this->actingAs($env['adminUser'])->post(route('admin.subjects.store'), [
            'grade_level_id' => $env['gradeLevel']->id,
            'subjects' => [
                [
                    'name' => 'DurationTestSubject',
                    'code' => 'DUR-'.fake()->unique()->lexify('???'),
                    'duration_minutes' => 45,
                ],
            ],
        ]);

        $response->assertRedirect(route('admin.subjects.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('subjects', [
            'name' => 'DurationTestSubject',
            'duration_minutes' => 45,
        ]);
    }

    public function test_subject_store_allows_null_duration(): void
    {
        $env = $this->buildTestEnvironment();

        $response = $this->actingAs($env['adminUser'])->post(route('admin.subjects.store'), [
            'grade_level_id' => $env['gradeLevel']->id,
            'subjects' => [
                [
                    'name' => 'NullDurSubject',
                    'code' => 'NDR-'.fake()->unique()->lexify('???'),
                ],
            ],
        ]);

        $response->assertRedirect(route('admin.subjects.index'));

        $this->assertDatabaseHas('subjects', [
            'name' => 'NullDurSubject',
            'duration_minutes' => null,
        ]);
    }

    public function test_subject_store_rejects_duration_below_minimum(): void
    {
        $env = $this->buildTestEnvironment();

        $response = $this->actingAs($env['adminUser'])->post(route('admin.subjects.store'), [
            'grade_level_id' => $env['gradeLevel']->id,
            'subjects' => [
                [
                    'name' => 'TooShortSubject',
                    'code' => 'TSS-'.fake()->unique()->lexify('???'),
                    'duration_minutes' => 5,
                ],
            ],
        ]);

        $response->assertSessionHasErrors('subjects.0.duration_minutes');
    }

    public function test_subject_store_rejects_duration_above_maximum(): void
    {
        $env = $this->buildTestEnvironment();

        $response = $this->actingAs($env['adminUser'])->post(route('admin.subjects.store'), [
            'grade_level_id' => $env['gradeLevel']->id,
            'subjects' => [
                [
                    'name' => 'TooLongSubject',
                    'code' => 'TLS-'.fake()->unique()->lexify('???'),
                    'duration_minutes' => 999,
                ],
            ],
        ]);

        $response->assertSessionHasErrors('subjects.0.duration_minutes');
    }

    public function test_subject_update_saves_duration_minutes(): void
    {
        $env = $this->buildTestEnvironment();

        $subject = Subject::create([
            'name' => 'UpdateDurSubject',
            'code' => 'UDS-'.fake()->unique()->lexify('???'),
            'grade_level_id' => $env['gradeLevel']->id,
            'description' => 'Test',
        ]);

        $response = $this->actingAs($env['adminUser'])->put(route('admin.subjects.update', $subject), [
            'name' => $subject->name,
            'code' => $subject->code,
            'grade_level_id' => $subject->grade_level_id,
            'duration_minutes' => 90,
        ]);

        $response->assertRedirect(route('admin.subjects.index'));

        $subject->refresh();
        $this->assertEquals(90, $subject->duration_minutes);
    }

    // ─── Auto-Schedule Default Time Tests ──────────────────────────────

    public function test_assign_adviser_creates_schedules_with_valid_default_times(): void
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
            $this->assertNotEquals('00:00:00', $schedule->start_time, 'Schedule should not have 00:00 start_time');
            $this->assertNotEquals('00:00:00', $schedule->end_time, 'Schedule should not have 00:00 end_time');
            $this->assertNotEquals('00:00', $schedule->start_time, 'Schedule should not have 00:00 start_time');
            $this->assertNotEquals('00:00', $schedule->end_time, 'Schedule should not have 00:00 end_time');
        }
    }

    public function test_assign_adviser_nulls_subject_duration_and_uses_60_minute_slots(): void
    {
        $env = $this->buildTestEnvironment(2);

        $subject = Subject::firstOrCreate(
            ['code' => 'DUR-ADV-'.fake()->unique()->lexify('???')],
            [
                'grade_level_id' => $env['gradeLevel']->id,
                'name' => 'DurationAdviserSubj',
                'description' => 'Test',
                'duration_minutes' => 45,
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

        $subject->refresh();
        $this->assertNull($subject->duration_minutes);

        if ($schedule) {
            $startTime = strtotime($schedule->start_time);
            $endTime = strtotime($schedule->end_time);
            $actualDuration = ($endTime - $startTime) / 60;

            $this->assertEquals(60, $actualDuration, 'End time should use the fixed 60-minute slot.');
        }
    }

    public function test_assign_adviser_defaults_to_60_minutes_without_duration(): void
    {
        $env = $this->buildTestEnvironment(3);

        $subject = Subject::firstOrCreate(
            ['code' => 'NODUR-'.fake()->unique()->lexify('???')],
            [
                'grade_level_id' => $env['gradeLevel']->id,
                'name' => 'NoDurationSubj',
                'description' => 'Test',
                'duration_minutes' => null,
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

        if ($schedule) {
            $startTime = strtotime($schedule->start_time);
            $endTime = strtotime($schedule->end_time);
            $actualDuration = ($endTime - $startTime) / 60;

            $this->assertEquals(60, $actualDuration, 'Should default to 60 minutes when subject has no duration');
        }
    }

    public function test_assign_adviser_creates_staggered_non_overlapping_schedules(): void
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
                'duration_minutes' => 60,
            ]);
        }

        $response = $this->actingAs($env['adminUser'])->post(
            route('admin.sections.adviser.assign', $env['class']),
            ['teacher_id' => $env['teacher']->id]
        );

        $response->assertRedirect();

        $schedules = Schedule::where('class_id', $env['class']->id)
            ->where('teacher_id', $env['teacher']->id)
            ->orderBy('start_time')
            ->get();

        $this->assertGreaterThanOrEqual(3, $schedules->count(), 'Should have created schedules for all subjects');

        // Verify no two schedules overlap
        for ($i = 1; $i < $schedules->count(); $i++) {
            $prevEnd = $schedules[$i - 1]->end_time->format('H:i');
            $currStart = $schedules[$i]->start_time->format('H:i');

            $this->assertGreaterThanOrEqual(
                strtotime($prevEnd),
                strtotime($currStart),
                "Schedule {$i} starts at {$currStart} which is before previous schedule ends at {$prevEnd}"
            );
        }
    }
}
