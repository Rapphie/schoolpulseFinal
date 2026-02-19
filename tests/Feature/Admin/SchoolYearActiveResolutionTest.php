<?php

namespace Tests\Feature\Admin;

use App\Models\SchoolYear;
use App\Models\SchoolYearQuarter;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class SchoolYearActiveResolutionTest extends TestCase
{
    use DatabaseTransactions;

    public function test_get_real_active_repairs_when_manual_quarter_exists_but_no_active_school_year(): void
    {
        SchoolYear::query()->update(['is_active' => false]);
        SchoolYearQuarter::query()->update(['is_manually_set_active' => false]);

        $schoolYearWithManualQuarter = SchoolYear::create([
            'name' => '2096-2097-active-resolution-a',
            'start_date' => '2096-06-01',
            'end_date' => '2097-03-31',
            'is_active' => false,
        ]);

        $otherSchoolYear = SchoolYear::create([
            'name' => '2097-2098-active-resolution-b',
            'start_date' => '2097-06-01',
            'end_date' => '2098-03-31',
            'is_active' => false,
        ]);

        $manualQuarter = SchoolYearQuarter::create([
            'school_year_id' => $schoolYearWithManualQuarter->id,
            'quarter' => 1,
            'name' => 'First Quarter',
            'start_date' => '2096-06-01',
            'end_date' => '2096-08-14',
            'is_locked' => false,
            'is_manually_set_active' => true,
        ]);

        $resolvedSchoolYear = SchoolYear::getRealActive();

        $this->assertNotNull($resolvedSchoolYear);
        $this->assertEquals($schoolYearWithManualQuarter->id, $resolvedSchoolYear->id);

        $schoolYearWithManualQuarter->refresh();
        $otherSchoolYear->refresh();
        $manualQuarter->refresh();

        $this->assertTrue($schoolYearWithManualQuarter->is_active);
        $this->assertFalse($otherSchoolYear->is_active);
        $this->assertTrue($manualQuarter->is_manually_set_active);
        $this->assertEquals(1, SchoolYear::query()->where('is_active', true)->count());
        $this->assertEquals(1, SchoolYearQuarter::query()->where('is_manually_set_active', true)->count());
    }

    public function test_get_real_active_returns_null_when_no_active_year_and_no_manual_quarter(): void
    {
        SchoolYear::query()->update(['is_active' => false]);
        SchoolYearQuarter::query()->update(['is_manually_set_active' => false]);

        SchoolYear::create([
            'name' => '2094-2095-active-resolution-none-a',
            'start_date' => '2094-06-01',
            'end_date' => '2095-03-31',
            'is_active' => false,
        ]);

        SchoolYear::create([
            'name' => '2095-2096-active-resolution-none-b',
            'start_date' => '2095-06-01',
            'end_date' => '2096-03-31',
            'is_active' => false,
        ]);

        $this->assertNull(SchoolYear::getRealActive());
    }
}
