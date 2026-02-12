<?php

namespace Tests\Feature\Admin;

use App\Models\SchoolYear;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class SchoolYearViewModeTest extends TestCase
{
    use DatabaseTransactions;

    private function getAdminUser(): User
    {
        $adminUser = User::where('role_id', 1)->first();
        if (! $adminUser) {
            $this->markTestSkipped('No admin user found.');
        }

        return $adminUser;
    }

    /**
     * @return array{active: SchoolYear, historical: SchoolYear}
     */
    private function createSampleSchoolYears(): array
    {
        $activeSchoolYear = SchoolYear::create([
            'name' => '2098-2099-view-active',
            'start_date' => '2098-06-01',
            'end_date' => '2099-03-31',
            'is_active' => true,
        ]);

        $historicalSchoolYear = SchoolYear::create([
            'name' => '2097-2098-view-history',
            'start_date' => '2097-06-01',
            'end_date' => '2098-03-31',
            'is_active' => false,
        ]);

        return [
            'active' => $activeSchoolYear,
            'historical' => $historicalSchoolYear,
        ];
    }

    public function test_admin_can_view_historical_school_year_without_changing_global_active(): void
    {
        $adminUser = $this->getAdminUser();
        $schoolYears = $this->createSampleSchoolYears();

        $response = $this->actingAs($adminUser)
            ->post(route('admin.school-year.view', $schoolYears['historical']->id));

        $response->assertRedirect(route('admin.dashboard'));
        $response->assertSessionHas('warning');
        $response->assertSessionHas(
            SchoolYear::ADMIN_VIEW_SCHOOL_YEAR_SESSION_KEY,
            $schoolYears['historical']->id
        );

        $schoolYears['active']->refresh();
        $schoolYears['historical']->refresh();

        $this->assertTrue($schoolYears['active']->is_active);
        $this->assertFalse($schoolYears['historical']->is_active);
    }

    public function test_admin_can_clear_view_mode_and_return_to_real_active_school_year(): void
    {
        $adminUser = $this->getAdminUser();
        $schoolYears = $this->createSampleSchoolYears();

        $response = $this->actingAs($adminUser)
            ->withSession([
                SchoolYear::ADMIN_VIEW_SCHOOL_YEAR_SESSION_KEY => $schoolYears['historical']->id,
            ])
            ->post(route('admin.school-year.view.reset'));

        $response->assertRedirect(route('admin.dashboard'));
        $response->assertSessionHas('success');
        $response->assertSessionMissing(SchoolYear::ADMIN_VIEW_SCHOOL_YEAR_SESSION_KEY);
    }

    public function test_setting_school_year_active_requires_confirm_keyword(): void
    {
        $adminUser = $this->getAdminUser();
        $schoolYears = $this->createSampleSchoolYears();

        $response = $this->actingAs($adminUser)
            ->from(route('admin.dashboard'))
            ->post(route('admin.school-year.set-active', $schoolYears['historical']->id), [
                'confirmation' => 'confirm',
            ]);

        $response->assertRedirect(route('admin.dashboard'));
        $response->assertSessionHasErrors('confirmation');

        $schoolYears['active']->refresh();
        $schoolYears['historical']->refresh();

        $this->assertTrue($schoolYears['active']->is_active);
        $this->assertFalse($schoolYears['historical']->is_active);
    }

    public function test_setting_school_year_active_with_confirm_updates_global_active_and_clears_view_mode(): void
    {
        $adminUser = $this->getAdminUser();
        $schoolYears = $this->createSampleSchoolYears();

        $response = $this->actingAs($adminUser)
            ->withSession([
                SchoolYear::ADMIN_VIEW_SCHOOL_YEAR_SESSION_KEY => $schoolYears['historical']->id,
            ])
            ->post(route('admin.school-year.set-active', $schoolYears['historical']->id), [
                'confirmation' => 'CONFIRM',
            ]);

        $response->assertRedirect(route('admin.dashboard'));
        $response->assertSessionHas('success');
        $response->assertSessionMissing(SchoolYear::ADMIN_VIEW_SCHOOL_YEAR_SESSION_KEY);

        $schoolYears['active']->refresh();
        $schoolYears['historical']->refresh();

        $this->assertFalse($schoolYears['active']->is_active);
        $this->assertTrue($schoolYears['historical']->is_active);
    }
}
