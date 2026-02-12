<?php

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
                'class_id' => $data['historicalClass']->id,
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
                'class_id' => $data['historicalClass']->id,
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
}
