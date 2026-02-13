<?php

namespace Tests\Feature;

use App\Models\Classes;
use App\Models\Enrollment;
use App\Models\GradeLevel;
use App\Models\SchoolYear;
use App\Models\Section;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use App\Services\StudentProfileService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class PanelFeedbackSchoolYearTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    /**
     * Helper to get or skip test dependencies.
     *
     * @return array{admin: User, teacher: Teacher, schoolYear: SchoolYear}
     */
    private function getBaseTestData(): array
    {
        $activeSchoolYear = SchoolYear::where('is_active', true)->first();
        if (! $activeSchoolYear) {
            $this->markTestSkipped('No active school year found.');
        }

        $adminUser = User::where('role_id', 1)->first();
        if (! $adminUser) {
            $this->markTestSkipped('No admin user found.');
        }

        $teacher = Teacher::whereHas('user')->first();
        if (! $teacher) {
            $this->markTestSkipped('No teacher with a user account found.');
        }

        return [
            'admin' => $adminUser,
            'teacher' => $teacher,
            'schoolYear' => $activeSchoolYear,
        ];
    }

    // ========================================================================
    // 1. Promotion toggle: blocked before school year ends
    // ========================================================================

    public function test_toggle_promotion_blocked_before_school_year_ends(): void
    {
        $data = $this->getBaseTestData();

        // Create a school year that has NOT ended yet (ends in the future)
        $futureSchoolYear = SchoolYear::create([
            'name' => '2027-2028',
            'start_date' => '2027-06-01',
            'end_date' => '2028-03-31',
            'is_active' => false,
            'is_promotion_open' => false,
        ]);

        $response = $this->actingAs($data['admin'])
            ->post(route('admin.school-year.toggle-promotion', $futureSchoolYear->id));

        $response->assertRedirect(route('admin.dashboard'));
        $response->assertSessionHas('error');

        $futureSchoolYear->refresh();
        $this->assertFalse($futureSchoolYear->is_promotion_open);
    }

    // ========================================================================
    // 2. Promotion toggle: allowed after school year ends
    // ========================================================================

    public function test_toggle_promotion_allowed_after_school_year_ends(): void
    {
        $data = $this->getBaseTestData();

        // Create a school year that HAS ended (in the past)
        $pastSchoolYear = SchoolYear::create([
            'name' => '2023-2024-test',
            'start_date' => '2023-06-01',
            'end_date' => '2024-03-31',
            'is_active' => false,
            'is_promotion_open' => false,
        ]);

        $response = $this->actingAs($data['admin'])
            ->post(route('admin.school-year.toggle-promotion', $pastSchoolYear->id));

        $response->assertRedirect(route('admin.dashboard'));
        $response->assertSessionHas('success');

        $pastSchoolYear->refresh();
        $this->assertTrue($pastSchoolYear->is_promotion_open);
    }

    // ========================================================================
    // 3. Promotion toggle: can close an already-open promotion
    // ========================================================================

    public function test_toggle_promotion_can_close_open_promotion(): void
    {
        $data = $this->getBaseTestData();

        $pastSchoolYear = SchoolYear::create([
            'name' => '2022-2023-test',
            'start_date' => '2022-06-01',
            'end_date' => '2023-03-31',
            'is_active' => false,
            'is_promotion_open' => true,
        ]);

        $response = $this->actingAs($data['admin'])
            ->post(route('admin.school-year.toggle-promotion', $pastSchoolYear->id));

        $response->assertRedirect(route('admin.dashboard'));
        $response->assertSessionHas('success');

        $pastSchoolYear->refresh();
        $this->assertFalse($pastSchoolYear->is_promotion_open);
    }

    // ========================================================================
    // 4. Dashboard displays slots per grade level
    // ========================================================================

    public function test_dashboard_shows_slots_per_grade_level(): void
    {
        $data = $this->getBaseTestData();

        $response = $this->actingAs($data['admin'])
            ->get(route('admin.dashboard'));

        $response->assertStatus(200);
        $response->assertSee('Available Slots Per Grade Level');
        $response->assertSee('slots remaining');
    }

    // ========================================================================
    // 5. Dashboard displays promotion column in school year table
    // ========================================================================

    public function test_dashboard_shows_promotion_column(): void
    {
        $data = $this->getBaseTestData();

        $response = $this->actingAs($data['admin'])
            ->get(route('admin.dashboard'));

        $response->assertStatus(200);
        $response->assertSee('Promotion');
    }

    // ========================================================================
    // 6. School year date overlap prevention
    // ========================================================================

    public function test_school_year_creation_prevents_date_overlaps(): void
    {
        $data = $this->getBaseTestData();
        $activeSchoolYear = $data['schoolYear'];

        // Try to create a school year that overlaps with the active one
        $response = $this->actingAs($data['admin'])->post(route('admin.school-year.store'), [
            'start_date' => $activeSchoolYear->start_date->format('Y-m-d'),
            'end_date' => $activeSchoolYear->end_date->format('Y-m-d'),
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    public function test_school_year_creation_allows_non_contiguous_dates(): void
    {
        $data = $this->getBaseTestData();

        SchoolYear::create([
            'name' => '2080-2081-test',
            'start_date' => '2080-06-01',
            'end_date' => '2081-03-31',
            'is_active' => false,
        ]);

        $response = $this->actingAs($data['admin'])->post(route('admin.school-year.store'), [
            'start_date' => '2081-06-01',
            'end_date' => '2082-03-31',
            'is_active' => false,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('school_years', [
            'name' => '2081-2082',
            'start_date' => '2081-06-01',
            'end_date' => '2082-03-31',
        ]);
    }

    public function test_school_year_update_allows_non_contiguous_dates(): void
    {
        $data = $this->getBaseTestData();

        SchoolYear::create([
            'name' => '2083-2084-test',
            'start_date' => '2083-06-01',
            'end_date' => '2084-03-31',
            'is_active' => false,
        ]);

        $editableYear = SchoolYear::create([
            'name' => '2084-2085-test',
            'start_date' => '2084-04-01',
            'end_date' => '2085-03-31',
            'is_active' => false,
        ]);

        $response = $this->actingAs($data['admin'])->put(route('admin.school-year.update', $editableYear->id), [
            'start_date' => '2084-06-01',
            'end_date' => '2085-03-31',
            'is_active' => false,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('school_years', [
            'id' => $editableYear->id,
            'name' => '2084-2085',
            'start_date' => '2084-06-01',
            'end_date' => '2085-03-31',
        ]);
    }

    // ========================================================================
    // 7. Enrollment blocked when promotion not open for non-active SY
    // ========================================================================

    public function test_enrollment_blocked_when_promotion_not_open(): void
    {
        $data = $this->getBaseTestData();

        $closedSchoolYear = SchoolYear::create([
            'name' => '2021-2022-test',
            'start_date' => '2021-06-01',
            'end_date' => '2022-03-31',
            'is_active' => false,
            'is_promotion_open' => false,
        ]);

        $gradeLevel = GradeLevel::where('level', 1)->first();
        if (! $gradeLevel) {
            $this->markTestSkipped('Grade level 1 not found.');
        }

        $section = Section::where('grade_level_id', $gradeLevel->id)->first();
        if (! $section) {
            $this->markTestSkipped('No section for grade level 1 found.');
        }

        $class = Classes::create([
            'section_id' => $section->id,
            'school_year_id' => $closedSchoolYear->id,
            'teacher_id' => $data['teacher']->id,
            'capacity' => 40,
        ]);

        $student = Student::first();
        if (! $student) {
            $this->markTestSkipped('No student found.');
        }

        $service = new StudentProfileService;

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Enrollment for this school year is not open.');

        $service->createEnrollmentWithProfile([
            'student_id' => $student->id,
            'class_id' => $class->id,
            'school_year_id' => $closedSchoolYear->id,
            'teacher_id' => $data['teacher']->id,
            'enrollment_date' => now(),
            'status' => 'enrolled',
        ]);
    }

    // ========================================================================
    // 8. SchoolYear model: hasEnded and canOpenPromotion
    // ========================================================================

    public function test_school_year_has_ended_returns_correct_value(): void
    {
        $pastSchoolYear = SchoolYear::create([
            'name' => '2020-2021-test',
            'start_date' => '2020-06-01',
            'end_date' => '2021-03-31',
            'is_active' => false,
        ]);

        $futureSchoolYear = SchoolYear::create([
            'name' => '2028-2029-test',
            'start_date' => '2028-06-01',
            'end_date' => '2029-03-31',
            'is_active' => false,
        ]);

        $this->assertTrue($pastSchoolYear->hasEnded());
        $this->assertTrue($pastSchoolYear->canOpenPromotion());
        $this->assertFalse($futureSchoolYear->hasEnded());
        $this->assertFalse($futureSchoolYear->canOpenPromotion());
    }

    // ========================================================================
    // 9. Auto-generate quarters divides school year evenly
    // ========================================================================

    public function test_auto_generate_quarters_creates_four_quarters(): void
    {
        $data = $this->getBaseTestData();

        $schoolYear = SchoolYear::create([
            'name' => '2024-2025-qtest',
            'start_date' => '2024-06-01',
            'end_date' => '2025-03-31',
            'is_active' => false,
        ]);

        $response = $this->actingAs($data['admin'])
            ->post(route('admin.school-year.quarters.auto-generate', $schoolYear->id));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertEquals(4, $schoolYear->quarters()->count());

        $quarters = $schoolYear->quarters()->orderBy('quarter')->get();
        $this->assertEquals(1, $quarters[0]->quarter);
        $this->assertEquals(4, $quarters[3]->quarter);

        // Ensure no gaps: each quarter starts the day after the previous ends
        for ($i = 1; $i < 4; $i++) {
            $this->assertEquals(
                $quarters[$i - 1]->end_date->addDay()->toDateString(),
                $quarters[$i]->start_date->toDateString(),
                "Quarter {$quarters[$i]->quarter} should start the day after Quarter {$quarters[$i - 1]->quarter} ends."
            );
        }

        // First quarter starts on school year start
        $this->assertEquals($schoolYear->start_date->toDateString(), $quarters[0]->start_date->toDateString());
        // Last quarter ends on school year end
        $this->assertEquals($schoolYear->end_date->toDateString(), $quarters[3]->end_date->toDateString());
    }
}
