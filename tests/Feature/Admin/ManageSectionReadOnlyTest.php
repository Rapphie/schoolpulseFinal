<?php

// Tests for Read Only Sections

namespace Tests\Feature\Admin;

use App\Models\Classes;
use App\Models\GradeLevel;
use App\Models\SchoolYear;
use App\Models\Section;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ManageSectionReadOnlyTest extends TestCase
{
    use DatabaseTransactions;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::where('role_id', 1)->first();
    }

    /**
     * @return array{section: Section, activeClass: Classes, historicalClass: Classes, activeSchoolYear: SchoolYear, historicalSchoolYear: SchoolYear}
     */
    private function createSectionWithHistory(): array
    {
        $gradeLevel = GradeLevel::first();

        $section = Section::create([
            'name' => 'TestSec',
            'grade_level_id' => $gradeLevel->id,
            'description' => 'Test section for history',
        ]);

        $activeSchoolYear = SchoolYear::where('is_active', true)->first();

        $historicalSchoolYear = SchoolYear::create([
            'name' => '2020-2021',
            'start_date' => '2020-06-01',
            'end_date' => '2021-03-31',
            'is_active' => false,
        ]);

        $activeClass = Classes::create([
            'section_id' => $section->id,
            'school_year_id' => $activeSchoolYear->id,
            'capacity' => 40,
        ]);

        $historicalClass = Classes::create([
            'section_id' => $section->id,
            'school_year_id' => $historicalSchoolYear->id,
            'capacity' => 35,
        ]);

        return compact('section', 'activeClass', 'historicalClass', 'activeSchoolYear', 'historicalSchoolYear');
    }

    public function test_active_school_year_is_editable(): void
    {
        $data = $this->createSectionWithHistory();

        $response = $this->actingAs($this->admin)
            ->get(route('admin.sections.manage', $data['section']));

        $response->assertStatus(200);
        $response->assertDontSee('You are viewing a historical school year');
        $response->assertSee('Enroll New Student');
        $response->assertSee('Delete');
    }

    public function test_historical_school_year_is_read_only(): void
    {
        $data = $this->createSectionWithHistory();

        $response = $this->actingAs($this->admin)
            ->get(route('admin.sections.manage', [
                'section' => $data['section'],
                'school_year_id' => $data['historicalSchoolYear']->id,
            ]));

        $response->assertStatus(200);
        $response->assertSee('You are viewing a historical school year');
        $response->assertSee($data['historicalSchoolYear']->name);
        $response->assertDontSee('Enroll New Student');
    }

    public function test_historical_view_hides_adviser_actions(): void
    {
        $data = $this->createSectionWithHistory();

        $response = $this->actingAs($this->admin)
            ->get(route('admin.sections.manage', [
                'section' => $data['section'],
                'school_year_id' => $data['historicalSchoolYear']->id,
            ]));

        $response->assertStatus(200);
        $response->assertDontSee('Assign Adviser');
        $response->assertDontSee('Change Adviser');
        $response->assertDontSee('Update Capacity');
    }

    public function test_active_view_shows_adviser_actions(): void
    {
        $data = $this->createSectionWithHistory();

        $response = $this->actingAs($this->admin)
            ->get(route('admin.sections.manage', $data['section']));

        $response->assertStatus(200);
        $response->assertSee('Assign Adviser');
        $response->assertSee('Update Capacity');
    }

    public function test_manage_page_links_enroll_to_admin_enrollment_create_page(): void
    {
        $data = $this->createSectionWithHistory();

        $response = $this->actingAs($this->admin)
            ->get(route('admin.sections.manage', $data['section']));

        $response->assertStatus(200);
        $response->assertSee('Enroll New Student');
        $response->assertSee('admin/classes/'.$data['activeClass']->id.'/enroll', false);
        $response->assertDontSee('id="enrollStudentModal"', false);
    }

    public function test_admin_enrollment_page_shows_school_year_filter_and_download_button(): void
    {
        $this->createSectionWithHistory();

        $response = $this->actingAs($this->admin)
            ->get(route('admin.enrollment.index'));

        $response->assertStatus(200);
        $response->assertSee('Student Enrollment');
        $response->assertSee('My Enrollments');
        $response->assertSee('Download Enrollees');
        $response->assertSee('admin/enrollment/export-mine', false);
        $response->assertSee('name="school_year_id"', false);
        $response->assertSee('(Current Active)');
    }

    public function test_admin_enrollment_school_year_filter_includes_selected_closed_year(): void
    {
        $data = $this->createSectionWithHistory();
        $data['activeSchoolYear']->update([
            'is_active' => false,
            'is_promotion_open' => false,
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.enrollment.index', [
                'school_year_id' => $data['activeSchoolYear']->id,
            ]));

        $response->assertStatus(200);
        $response->assertSee('name="school_year_id"', false);
        $response->assertSee($data['activeSchoolYear']->name);
    }

    public function test_admin_enrollment_non_active_school_year_is_view_mode(): void
    {
        $data = $this->createSectionWithHistory();

        $response = $this->actingAs($this->admin)
            ->get(route('admin.enrollment.index', [
                'school_year_id' => $data['historicalSchoolYear']->id,
            ]));

        $response->assertStatus(200);
        $response->assertSee('My Enrollments');
        $response->assertSee('Download Enrollees');
        $response->assertSee('id="enrollmentViewModeAlert"', false);
        $response->assertSee('data-readonly="true"', false);
    }

    public function test_admin_enrollment_uses_real_active_fallback_for_view_mode_check(): void
    {
        $data = $this->createSectionWithHistory();

        $data['activeSchoolYear']->update([
            'is_active' => false,
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.enrollment.index', [
                'school_year_id' => $data['activeSchoolYear']->id,
            ]));

        $response->assertStatus(200);
        $response->assertSee('My Enrollments');
        $response->assertDontSee('id="enrollmentViewModeAlert"', false);
        $response->assertSee('data-readonly="false"', false);
    }

    public function test_admin_cannot_store_past_student_in_non_active_school_year(): void
    {
        $data = $this->createSectionWithHistory();

        $response = $this->actingAs($this->admin)
            ->post(route('admin.enrollment.page.storePastStudent'), [
                'student_id' => '999999',
                'class_id' => $data['historicalClass']->id,
            ]);

        $response->assertRedirect(route('admin.enrollment.index', [
            'school_year_id' => $data['historicalSchoolYear']->id,
        ]));
        $response->assertSessionHas('error', 'Enrollment is view-only for non-active school years. Select the current active school year to enroll.');
    }
}
