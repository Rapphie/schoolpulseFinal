<?php

namespace Tests\Feature\Admin;

use App\Models\Role;
use App\Models\SchoolYear;
use App\Models\SchoolYearMonthDay;
use App\Models\SchoolYearQuarter;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class SettingsSchoolYearMonthDaysWarningTest extends TestCase
{
    use DatabaseTransactions;

    public function test_warning_is_shown_when_active_school_year_month_days_are_incomplete(): void
    {
        $this->withoutVite();
        SchoolYear::query()->update(['is_active' => false]);
        SchoolYearQuarter::query()->update(['is_manually_set_active' => false]);

        $admin = $this->createAdminUser();

        SchoolYear::create([
            'name' => '2099-2100',
            'start_date' => '2099-05-01',
            'end_date' => '2100-08-31',
            'is_active' => true,
            'is_promotion_open' => false,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.settings.index', ['panel' => 'school_year_month_days']))
            ->assertStatus(200)
            ->assertSee('Active school year school days is not set for all months.');
    }

    public function test_warning_is_not_shown_when_active_school_year_month_days_are_complete(): void
    {
        $this->withoutVite();
        SchoolYear::query()->update(['is_active' => false]);
        SchoolYearQuarter::query()->update(['is_manually_set_active' => false]);

        $admin = $this->createAdminUser();

        $schoolYear = SchoolYear::create([
            'name' => '2101-2102',
            'start_date' => '2101-06-01',
            'end_date' => '2101-08-31',
            'is_active' => true,
            'is_promotion_open' => false,
        ]);

        SchoolYearMonthDay::create([
            'school_year_id' => $schoolYear->id,
            'month' => 6,
            'school_days' => 11,
        ]);

        SchoolYearMonthDay::create([
            'school_year_id' => $schoolYear->id,
            'month' => 7,
            'school_days' => 23,
        ]);

        SchoolYearMonthDay::create([
            'school_year_id' => $schoolYear->id,
            'month' => 8,
            'school_days' => 20,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.settings.index', ['panel' => 'school_year_month_days']))
            ->assertStatus(200)
            ->assertDontSee('Active school year school days is not set for all months.');
    }

    private function createAdminUser(): User
    {
        Role::firstOrCreate(
            ['id' => 1],
            ['name' => 'admin', 'description' => 'Administrator']
        );

        return User::factory()->create([
            'role_id' => 1,
        ]);
    }
}
