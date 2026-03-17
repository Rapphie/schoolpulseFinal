<?php

namespace Tests\Feature;

use App\Models\Classes;
use App\Models\Enrollment;
use App\Models\Grade;
use App\Models\GradeLevel;
use App\Models\Role;
use App\Models\Schedule;
use App\Models\SchoolYear;
use App\Models\SchoolYearQuarter;
use App\Models\Section;
use App\Models\Student;
use App\Models\StudentProfile;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use App\Services\GradeService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Tests\TestCase;

class PanelFeedbackRemainingFixesTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    /**
     * Create a role by name, reusing if it already exists.
     */
    private function ensureRole(string $name, int $id): Role
    {
        return Role::firstOrCreate(['id' => $id], ['name' => $name, 'description' => ucfirst($name)]);
    }

    /**
     * Build a minimal test environment with a school year, grade level, section, class, teacher, and student.
     *
     * @return array{user: User, teacher: Teacher, schoolYear: SchoolYear, gradeLevel: GradeLevel, section: Section, class: Classes, student: Student, subject: Subject}
     */
    private function buildTestEnvironment(int $gradeLevelValue = 1): array
    {
        $this->ensureRole('admin', 1);
        $this->ensureRole('teacher', 2);

        $schoolYear = SchoolYear::firstOrCreate(
            ['name' => '2025-2026-test-remaining'],
            ['start_date' => '2025-06-01', 'end_date' => '2026-03-31', 'is_active' => true]
        );

        $gradeLevel = GradeLevel::firstOrCreate(
            ['level' => $gradeLevelValue],
            ['name' => 'Grade '.$gradeLevelValue, 'description' => 'Test grade level']
        );

        $section = Section::firstOrCreate(
            ['name' => 'TestSec-'.$gradeLevelValue, 'grade_level_id' => $gradeLevel->id],
            ['description' => 'Test section']
        );

        $user = User::factory()->create([
            'role_id' => 2,
            'temporary_password' => null,
        ]);

        $teacher = Teacher::create([
            'user_id' => $user->id,
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
            ['code' => 'TST-'.$gradeLevelValue.'-'.fake()->unique()->lexify('???')],
            ['grade_level_id' => $gradeLevel->id, 'name' => 'TestSubj-'.fake()->unique()->word(), 'description' => 'Test']
        );

        $student = Student::create([
            'student_id' => 'TST-'.now()->timestamp.'-'.fake()->numerify('###'),
            'lrn' => fake()->unique()->numerify('############'),
            'first_name' => 'Test',
            'last_name' => 'Student'.fake()->numerify('###'),
            'gender' => 'male',
            'birthdate' => '2015-01-01',
        ]);

        return compact('user', 'teacher', 'schoolYear', 'gradeLevel', 'section', 'class', 'student', 'subject');
    }

    // ========================================================================
    // 1. Grade Consistency: Grades view uses transmutation guard
    // ========================================================================

    public function test_grades_view_transmutes_raw_grades_below_60(): void
    {
        $env = $this->buildTestEnvironment();

        $profile = StudentProfile::create([
            'student_id' => $env['student']->id,
            'school_year_id' => $env['schoolYear']->id,
            'grade_level_id' => $env['gradeLevel']->id,
            'status' => 'enrolled',
        ]);

        Enrollment::create([
            'student_id' => $env['student']->id,
            'class_id' => $env['class']->id,
            'school_year_id' => $env['schoolYear']->id,
            'student_profile_id' => $profile->id,
            'teacher_id' => $env['teacher']->id,
            'enrollment_date' => now(),
            'status' => 'enrolled',
        ]);

        // Store a raw/untransmuted grade of 28 (which should be transmuted to 67)
        Grade::create([
            'student_id' => $env['student']->id,
            'student_profile_id' => $profile->id,
            'subject_id' => $env['subject']->id,
            'teacher_id' => $env['teacher']->id,
            'school_year_id' => $env['schoolYear']->id,
            'grade' => 28,
            'quarter' => 1,
        ]);

        $response = $this->actingAs($env['user'])
            ->get(route('teacher.students.grades', [$env['student'], $env['schoolYear']]));

        $response->assertStatus(200);

        // The view should show the transmuted value (67), not the raw value (28)
        $transmuted = GradeService::transmute(28);
        $this->assertEquals(67, $transmuted);

        $response->assertSee((string) $transmuted);
        $response->assertDontSee('>28<');
    }

    public function test_grades_view_does_not_alter_valid_transmuted_grades(): void
    {
        $env = $this->buildTestEnvironment();

        $profile = StudentProfile::create([
            'student_id' => $env['student']->id,
            'school_year_id' => $env['schoolYear']->id,
            'grade_level_id' => $env['gradeLevel']->id,
            'status' => 'enrolled',
        ]);

        Enrollment::create([
            'student_id' => $env['student']->id,
            'class_id' => $env['class']->id,
            'school_year_id' => $env['schoolYear']->id,
            'student_profile_id' => $profile->id,
            'teacher_id' => $env['teacher']->id,
            'enrollment_date' => now(),
            'status' => 'enrolled',
        ]);

        // Store an already-transmuted grade of 85
        Grade::create([
            'student_id' => $env['student']->id,
            'student_profile_id' => $profile->id,
            'subject_id' => $env['subject']->id,
            'teacher_id' => $env['teacher']->id,
            'school_year_id' => $env['schoolYear']->id,
            'grade' => 85,
            'quarter' => 1,
        ]);

        $response = $this->actingAs($env['user'])
            ->get(route('teacher.students.grades', [$env['student'], $env['schoolYear']]));

        $response->assertStatus(200);
        $response->assertSee('85');
    }

    public function test_grade_service_transmutation_matches_grades_view(): void
    {
        // Verify the transmutation logic used by both the report card and the grades view is identical
        $testCases = [
            28 => 67,
            0 => 60,
            100 => 100,
            59.9 => 74, // 59.9 >= 56.00 threshold → 74
            60 => 75,   // Exactly 60.00 threshold → 75
            75 => 84,   // 75 >= 74.40 threshold → 84
        ];

        foreach ($testCases as $raw => $expected) {
            $this->assertEquals(
                $expected,
                GradeService::transmute((float) $raw),
                "Expected transmute($raw) = $expected"
            );
        }
    }

    // ========================================================================
    // 2. SectionController: Block adviser checks on assignAdviser
    // ========================================================================

    public function test_section_controller_assign_adviser_blocks_block_adviser(): void
    {
        $this->ensureRole('admin', 1);
        $this->ensureRole('teacher', 2);
        SchoolYear::query()->update(['is_active' => false]);
        SchoolYearQuarter::query()->update(['is_manually_set_active' => false]);
        $suffix = Str::lower(Str::random(6));

        $adminUser = User::factory()->create(['role_id' => 1, 'temporary_password' => null]);

        $schoolYear = SchoolYear::create([
            'name' => '2025-2026-test-remaining-'.$suffix,
            'start_date' => '2025-06-01',
            'end_date' => '2026-03-31',
            'is_active' => true,
        ]);

        $grade1 = GradeLevel::firstOrCreate(['level' => 1], ['name' => 'Grade 1', 'description' => 'G1']);
        $grade4 = GradeLevel::firstOrCreate(['level' => 4], ['name' => 'Grade 4', 'description' => 'G4']);

        // Create teacher
        $teacherUser = User::factory()->create(['role_id' => 2, 'temporary_password' => null]);
        $teacher = Teacher::create([
            'user_id' => $teacherUser->id,
            'phone' => '09123456781',
            'gender' => 'male',
            'date_of_birth' => '1990-01-01',
            'address' => 'Test',
            'qualification' => 'BSEd',
            'status' => 'active',
        ]);

        // Make this teacher a Grade 1 block adviser
        $section1 = Section::create([
            'name' => 'BlockSec-Test-'.$suffix,
            'grade_level_id' => $grade1->id,
            'description' => 'Block section',
        ]);
        Classes::create([
            'section_id' => $section1->id,
            'school_year_id' => $schoolYear->id,
            'teacher_id' => $teacher->id,
            'capacity' => 40,
        ]);

        // Try to assign same teacher as adviser to a Grade 4 class via the route
        $section4 = Section::create([
            'name' => 'DeptSec-Test-'.$suffix,
            'grade_level_id' => $grade4->id,
            'description' => 'Dept section',
        ]);
        $class4 = Classes::create([
            'section_id' => $section4->id,
            'school_year_id' => $schoolYear->id,
            'teacher_id' => null,
            'capacity' => 40,
        ]);

        $response = $this->actingAs($adminUser)
            ->post(route('admin.sections.adviser.assign', $class4), [
                'teacher_id' => $teacher->id,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    // ========================================================================
    // 3. SectionController: Block Grade 1-3 manual schedule + time conflicts
    // ========================================================================

    public function test_section_controller_store_schedule_blocks_grade_1_3(): void
    {
        $this->ensureRole('admin', 1);
        $this->ensureRole('teacher', 2);

        $adminUser = User::factory()->create(['role_id' => 1, 'temporary_password' => null]);

        $schoolYear = SchoolYear::firstOrCreate(
            ['name' => '2025-2026-test-remaining'],
            ['start_date' => '2025-06-01', 'end_date' => '2026-03-31', 'is_active' => true]
        );

        $grade1 = GradeLevel::firstOrCreate(['level' => 1], ['name' => 'Grade 1', 'description' => 'G1']);

        $teacherUser = User::factory()->create(['role_id' => 2, 'temporary_password' => null]);
        $teacher = Teacher::create([
            'user_id' => $teacherUser->id,
            'phone' => '09123456782',
            'gender' => 'female',
            'date_of_birth' => '1992-05-15',
            'address' => 'Test',
            'qualification' => 'BSEd',
            'status' => 'active',
        ]);

        $section = Section::firstOrCreate(
            ['name' => 'G1-SchedTest', 'grade_level_id' => $grade1->id],
            ['description' => 'Grade 1 section']
        );
        $class = Classes::firstOrCreate(
            ['section_id' => $section->id, 'school_year_id' => $schoolYear->id],
            ['teacher_id' => $teacher->id, 'capacity' => 40]
        );

        $subject = Subject::firstOrCreate(
            ['code' => 'G1-MATH-TEST'],
            ['grade_level_id' => $grade1->id, 'name' => 'Math Test G1', 'description' => 'Test']
        );

        // Attempt manual schedule creation for a Grade 1 class — should be blocked
        $response = $this->actingAs($adminUser)
            ->post(route('admin.sections.schedule.store', $class), [
                'subject_id' => $subject->id,
                'teacher_id' => $teacher->id,
                'day_of_week' => ['monday'],
                'start_time' => '08:00',
                'end_time' => '09:00',
                'room' => 'Room 1',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    // ========================================================================
    // 4. SlotsPerGrade: Caching and no N+1
    // ========================================================================

    public function test_admin_dashboard_caches_slots_per_grade(): void
    {
        $this->ensureRole('admin', 1);

        $adminUser = User::factory()->create(['role_id' => 1, 'temporary_password' => null]);

        $schoolYear = SchoolYear::where('is_active', true)->first();
        if (! $schoolYear) {
            $this->markTestSkipped('No active school year found.');
        }

        // Clear any existing cache
        Cache::forget('slots_per_grade_'.$schoolYear->id);

        // First request — should populate the cache
        $this->actingAs($adminUser)->get(route('admin.dashboard'));

        $this->assertTrue(Cache::has('slots_per_grade_'.$schoolYear->id));

        // Verify cache contents are a collection with expected shape
        $cached = Cache::get('slots_per_grade_'.$schoolYear->id);
        $this->assertNotNull($cached);

        if ($cached->isNotEmpty()) {
            $first = $cached->first();
            $this->assertArrayHasKey('name', $first);
            $this->assertArrayHasKey('level', $first);
            $this->assertArrayHasKey('total_capacity', $first);
            $this->assertArrayHasKey('enrolled', $first);
            $this->assertArrayHasKey('available', $first);
            $this->assertArrayHasKey('class_count', $first);
        }
    }

    public function test_admin_dashboard_slots_per_grade_values_are_correct(): void
    {
        $this->ensureRole('admin', 1);

        $adminUser = User::factory()->create(['role_id' => 1, 'temporary_password' => null]);

        $schoolYear = SchoolYear::where('is_active', true)->first();
        if (! $schoolYear) {
            $this->markTestSkipped('No active school year found.');
        }

        Cache::forget('slots_per_grade_'.$schoolYear->id);

        $response = $this->actingAs($adminUser)->get(route('admin.dashboard'));
        $response->assertStatus(200);

        $cached = Cache::get('slots_per_grade_'.$schoolYear->id);

        if ($cached && $cached->isNotEmpty()) {
            foreach ($cached as $gradeSlot) {
                $this->assertGreaterThanOrEqual(0, $gradeSlot['available']);
                $this->assertGreaterThanOrEqual(0, $gradeSlot['enrolled']);
                $this->assertEquals(
                    max(0, $gradeSlot['total_capacity'] - $gradeSlot['enrolled']),
                    $gradeSlot['available'],
                    'Available slots should equal capacity minus enrolled'
                );
            }
        }
    }

    // ========================================================================
    // 5. SectionController: Teacher already adviser elsewhere
    // ========================================================================

    public function test_section_controller_blocks_teacher_already_adviser_elsewhere(): void
    {
        $this->ensureRole('admin', 1);
        $this->ensureRole('teacher', 2);
        SchoolYear::query()->update(['is_active' => false]);
        SchoolYearQuarter::query()->update(['is_manually_set_active' => false]);
        $suffix = Str::lower(Str::random(6));

        $adminUser = User::factory()->create(['role_id' => 1, 'temporary_password' => null]);

        $schoolYear = SchoolYear::create([
            'name' => '2025-2026-test-remaining-'.$suffix,
            'start_date' => '2025-06-01',
            'end_date' => '2026-03-31',
            'is_active' => true,
        ]);

        $grade4 = GradeLevel::firstOrCreate(['level' => 4], ['name' => 'Grade 4', 'description' => 'G4']);
        $grade5 = GradeLevel::firstOrCreate(['level' => 5], ['name' => 'Grade 5', 'description' => 'G5']);

        $teacherUser = User::factory()->create(['role_id' => 2, 'temporary_password' => null]);
        $teacher = Teacher::create([
            'user_id' => $teacherUser->id,
            'phone' => '09123456783',
            'gender' => 'male',
            'date_of_birth' => '1988-03-10',
            'address' => 'Test',
            'qualification' => 'BSEd',
            'status' => 'active',
        ]);

        // Teacher is already adviser to a Grade 4 section
        $sectionA = Section::create([
            'name' => 'AdvA-Test-'.$suffix,
            'grade_level_id' => $grade4->id,
            'description' => 'Section A',
        ]);
        Classes::create([
            'section_id' => $sectionA->id,
            'school_year_id' => $schoolYear->id,
            'teacher_id' => $teacher->id,
            'capacity' => 40,
        ]);

        // Try to assign same teacher as adviser to another section
        $sectionB = Section::create([
            'name' => 'AdvB-Test-'.$suffix,
            'grade_level_id' => $grade5->id,
            'description' => 'Section B',
        ]);
        $classB = Classes::create([
            'section_id' => $sectionB->id,
            'school_year_id' => $schoolYear->id,
            'teacher_id' => null,
            'capacity' => 40,
        ]);

        $response = $this->actingAs($adminUser)
            ->post(route('admin.sections.adviser.assign', $classB), [
                'teacher_id' => $teacher->id,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    public function test_section_creation_rejects_duplicate_name_within_same_grade_level(): void
    {
        $this->ensureRole('admin', 1);

        $adminUser = User::factory()->create(['role_id' => 1, 'temporary_password' => null]);
        $schoolYear = SchoolYear::firstOrCreate(
            ['name' => '2025-2026-test-remaining'],
            ['start_date' => '2025-06-01', 'end_date' => '2026-03-31', 'is_active' => true]
        );

        $gradeLevel = GradeLevel::firstOrCreate(['level' => 3], ['name' => 'Grade 3', 'description' => 'G3']);
        $existingSection = Section::firstOrCreate(
            ['name' => 'Duplicate-Section-Test', 'grade_level_id' => $gradeLevel->id],
            ['description' => 'Duplicate check section']
        );

        Classes::firstOrCreate(
            ['section_id' => $existingSection->id, 'school_year_id' => $schoolYear->id],
            ['teacher_id' => null, 'capacity' => 40]
        );

        $response = $this->actingAs($adminUser)
            ->post(route('admin.sections.store'), [
                'name' => 'Duplicate-Section-Test',
                'grade_level_id' => $gradeLevel->id,
                'capacity' => 40,
            ]);

        $response->assertRedirect();
        $response->assertInvalid(['name']);
    }
}
