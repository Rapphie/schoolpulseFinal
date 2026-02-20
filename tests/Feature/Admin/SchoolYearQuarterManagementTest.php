<?php

namespace Tests\Feature\Admin;

use App\Models\SchoolYear;
use App\Models\SchoolYearQuarter;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class SchoolYearQuarterManagementTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class);
    }

    /**
     * @return array{admin: User, schoolYear: SchoolYear}
     */
    private function getBaseTestData(): array
    {
        SchoolYearQuarter::query()->update(['is_manually_set_active' => false]);
        SchoolYear::query()->update(['is_active' => false]);

        $adminUser = User::where('role_id', 1)->first();
        if (! $adminUser) {
            $this->markTestSkipped('No admin user found.');
        }

        $schoolYear = SchoolYear::create([
            'name' => '2027-2028-qtest',
            'start_date' => '2027-06-01',
            'end_date' => '2028-03-31',
            'is_active' => false,
        ]);

        return [
            'admin' => $adminUser,
            'schoolYear' => $schoolYear,
        ];
    }

    /**
     * @return array{admin: User, schoolYear: SchoolYear, quarter: SchoolYearQuarter}
     */
    private function getDataWithQuarter(): array
    {
        $data = $this->getBaseTestData();

        $quarter = $data['schoolYear']->quarters()->create([
            'quarter' => 1,
            'name' => 'First Quarter',
            'start_date' => '2027-06-01',
            'end_date' => '2027-08-14',
            'is_locked' => false,
            'is_manually_set_active' => false,
        ]);

        return array_merge($data, ['quarter' => $quarter]);
    }

    // ========================================================================
    // Toggle Lock
    // ========================================================================

    public function test_toggle_lock_locks_an_unlocked_quarter(): void
    {
        $data = $this->getDataWithQuarter();
        $data['schoolYear']->update(['is_active' => true]);

        $response = $this->actingAs($data['admin'])
            ->post(route('admin.school-year.quarters.toggle-lock', [
                $data['schoolYear'],
                $data['quarter'],
            ]));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $data['quarter']->refresh();
        $this->assertTrue($data['quarter']->is_locked);
    }

    public function test_toggle_lock_unlocks_a_locked_quarter(): void
    {
        $data = $this->getDataWithQuarter();
        $data['schoolYear']->update(['is_active' => true]);
        $data['quarter']->update(['is_locked' => true]);

        $response = $this->actingAs($data['admin'])
            ->post(route('admin.school-year.quarters.toggle-lock', [
                $data['schoolYear'],
                $data['quarter'],
            ]));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $data['quarter']->refresh();
        $this->assertFalse($data['quarter']->is_locked);
    }

    public function test_toggle_lock_fails_when_school_year_is_not_active(): void
    {
        $data = $this->getDataWithQuarter();

        $response = $this->actingAs($data['admin'])
            ->post(route('admin.school-year.quarters.toggle-lock', [
                $data['schoolYear'],
                $data['quarter'],
            ]));

        $response->assertRedirect();
        $response->assertSessionHas('error');

        $data['quarter']->refresh();
        $this->assertFalse($data['quarter']->is_locked);
    }

    // ========================================================================
    // Set Active Quarter
    // ========================================================================

    public function test_set_active_marks_quarter_as_manually_active(): void
    {
        $data = $this->getDataWithQuarter();
        $data['schoolYear']->update(['is_active' => true]);

        $response = $this->actingAs($data['admin'])
            ->post(route('admin.school-year.quarters.set-active', [
                $data['schoolYear'],
                $data['quarter'],
            ]));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $data['quarter']->refresh();
        $data['schoolYear']->refresh();

        $this->assertTrue($data['quarter']->is_manually_set_active);
        $this->assertTrue($data['schoolYear']->is_active);
    }

    public function test_set_active_deactivates_previous_manually_active_quarter(): void
    {
        $data = $this->getBaseTestData();
        $data['schoolYear']->update(['is_active' => true]);

        $quarter1 = $data['schoolYear']->quarters()->create([
            'quarter' => 1,
            'name' => 'First Quarter',
            'start_date' => '2027-06-01',
            'end_date' => '2027-08-14',
            'is_locked' => false,
            'is_manually_set_active' => true,
        ]);

        $quarter2 = $data['schoolYear']->quarters()->create([
            'quarter' => 2,
            'name' => 'Second Quarter',
            'start_date' => '2027-08-15',
            'end_date' => '2027-10-28',
            'is_locked' => false,
            'is_manually_set_active' => false,
        ]);

        $response = $this->actingAs($data['admin'])
            ->post(route('admin.school-year.quarters.set-active', [
                $data['schoolYear'],
                $quarter2,
            ]));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $quarter1->refresh();
        $quarter2->refresh();

        $this->assertFalse($quarter1->is_manually_set_active);
        $this->assertTrue($quarter2->is_manually_set_active);
    }

    public function test_set_active_fails_when_school_year_is_not_the_active_school_year(): void
    {
        SchoolYearQuarter::query()->update(['is_manually_set_active' => false]);
        SchoolYear::query()->update(['is_active' => false]);

        $adminUser = User::where('role_id', 1)->first();
        if (! $adminUser) {
            $this->markTestSkipped('No admin user found.');
        }

        $inactiveYear = SchoolYear::create([
            'name' => '2031-2032-qtest-inactive',
            'start_date' => '2031-06-01',
            'end_date' => '2032-03-31',
            'is_active' => false,
        ]);

        $activeYear = SchoolYear::create([
            'name' => '2032-2033-qtest-active',
            'start_date' => '2032-06-01',
            'end_date' => '2033-03-31',
            'is_active' => true,
        ]);

        $quarter = $inactiveYear->quarters()->create([
            'quarter' => 1,
            'name' => 'First Quarter',
            'start_date' => '2031-06-01',
            'end_date' => '2031-08-14',
            'is_locked' => false,
            'is_manually_set_active' => false,
        ]);

        $response = $this->actingAs($adminUser)
            ->post(route('admin.school-year.quarters.set-active', [$inactiveYear, $quarter]));

        $response->assertRedirect();
        $response->assertSessionHas('error');

        $inactiveYear->refresh();
        $activeYear->refresh();
        $quarter->refresh();

        $this->assertFalse($inactiveYear->is_active);
        $this->assertTrue($activeYear->is_active);
        $this->assertFalse($quarter->is_manually_set_active);
    }

    public function test_set_active_keeps_existing_active_school_year_when_setting_quarter_within_it(): void
    {
        SchoolYearQuarter::query()->update(['is_manually_set_active' => false]);
        SchoolYear::query()->update(['is_active' => false]);

        $adminUser = User::where('role_id', 1)->first();
        if (! $adminUser) {
            $this->markTestSkipped('No admin user found.');
        }

        $yearA = SchoolYear::create([
            'name' => '2028-2029-qtest-a',
            'start_date' => '2028-06-01',
            'end_date' => '2029-03-31',
            'is_active' => true,
        ]);

        $yearB = SchoolYear::create([
            'name' => '2029-2030-qtest-b',
            'start_date' => '2029-06-01',
            'end_date' => '2030-03-31',
            'is_active' => false,
        ]);

        $quarterA = $yearA->quarters()->create([
            'quarter' => 1,
            'name' => 'First Quarter',
            'start_date' => '2028-06-01',
            'end_date' => '2028-08-14',
            'is_locked' => false,
            'is_manually_set_active' => false,
        ]);

        $response = $this->actingAs($adminUser)
            ->post(route('admin.school-year.quarters.set-active', [$yearA, $quarterA]));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $yearA->refresh();
        $yearB->refresh();
        $quarterA->refresh();

        $this->assertTrue($yearA->is_active);
        $this->assertFalse($yearB->is_active);
        $this->assertTrue($quarterA->is_manually_set_active);
        $this->assertEquals(1, SchoolYear::query()->where('is_active', true)->count());
        $this->assertEquals(1, SchoolYearQuarter::query()->where('is_manually_set_active', true)->count());
    }

    // ========================================================================
    // Unset Active Quarter
    // ========================================================================

    public function test_unset_active_removes_manual_override(): void
    {
        $data = $this->getDataWithQuarter();
        $data['quarter']->update(['is_manually_set_active' => true]);

        $response = $this->actingAs($data['admin'])
            ->post(route('admin.school-year.quarters.unset-active', [
                $data['schoolYear'],
                $data['quarter'],
            ]));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $data['quarter']->refresh();
        $this->assertFalse($data['quarter']->is_manually_set_active);
    }

    public function test_unset_active_fails_if_quarter_is_not_manually_active(): void
    {
        $data = $this->getDataWithQuarter();

        $response = $this->actingAs($data['admin'])
            ->post(route('admin.school-year.quarters.unset-active', [
                $data['schoolYear'],
                $data['quarter'],
            ]));

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    // ========================================================================
    // Delete Quarter
    // ========================================================================

    public function test_delete_quarter_removes_it_from_database(): void
    {
        $data = $this->getDataWithQuarter();
        $quarterId = $data['quarter']->id;

        $response = $this->actingAs($data['admin'])
            ->delete(route('admin.school-year.quarters.destroy', [
                $data['schoolYear'],
                $data['quarter'],
            ]));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('school_year_quarters', ['id' => $quarterId]);
    }

    // ========================================================================
    // Store Quarter
    // ========================================================================

    public function test_store_quarter_creates_a_new_quarter(): void
    {
        $data = $this->getBaseTestData();

        $response = $this->actingAs($data['admin'])
            ->post(route('admin.school-year.quarters.store', $data['schoolYear']), [
                'quarter' => 1,
                'start_date' => '2027-06-01',
                'end_date' => '2027-08-14',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('school_year_quarters', [
            'school_year_id' => $data['schoolYear']->id,
            'quarter' => 1,
            'name' => 'First Quarter',
        ]);
    }

    public function test_store_quarter_fails_with_overlapping_dates(): void
    {
        $data = $this->getDataWithQuarter();

        $response = $this->actingAs($data['admin'])
            ->post(route('admin.school-year.quarters.store', $data['schoolYear']), [
                'quarter' => 2,
                'start_date' => '2027-07-01',
                'end_date' => '2027-09-15',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    // ========================================================================
    // Update Quarter
    // ========================================================================

    public function test_update_quarter_changes_dates(): void
    {
        $data = $this->getDataWithQuarter();

        $response = $this->actingAs($data['admin'])
            ->put(route('admin.school-year.quarters.update', [
                $data['schoolYear'],
                $data['quarter'],
            ]), [
                'start_date' => '2027-06-05',
                'end_date' => '2027-08-20',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $data['quarter']->refresh();
        $this->assertEquals('2027-06-05', $data['quarter']->start_date->toDateString());
        $this->assertEquals('2027-08-20', $data['quarter']->end_date->toDateString());
    }

    public function test_update_quarter_can_toggle_lock_via_is_locked_field(): void
    {
        $data = $this->getDataWithQuarter();
        $data['schoolYear']->update(['is_active' => true]);

        $response = $this->actingAs($data['admin'])
            ->put(route('admin.school-year.quarters.update', [
                $data['schoolYear'],
                $data['quarter'],
            ]), [
                'start_date' => '2027-06-01',
                'end_date' => '2027-08-14',
                'is_locked' => true,
            ]);

        $response->assertRedirect();
        $data['quarter']->refresh();
        $this->assertTrue($data['quarter']->is_locked);
    }

    public function test_update_quarter_cannot_change_lock_when_school_year_is_not_active(): void
    {
        $data = $this->getDataWithQuarter();

        $response = $this->actingAs($data['admin'])
            ->put(route('admin.school-year.quarters.update', [
                $data['schoolYear'],
                $data['quarter'],
            ]), [
                'start_date' => '2027-06-01',
                'end_date' => '2027-08-14',
                'is_locked' => true,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');

        $data['quarter']->refresh();
        $this->assertFalse($data['quarter']->is_locked);
    }

    // ========================================================================
    // Auto-Generate Quarters
    // ========================================================================

    public function test_auto_generate_creates_four_quarters(): void
    {
        $data = $this->getBaseTestData();

        $response = $this->actingAs($data['admin'])
            ->post(route('admin.school-year.quarters.auto-generate', $data['schoolYear']));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertEquals(4, $data['schoolYear']->quarters()->count());
    }

    public function test_auto_generate_fails_if_quarters_already_exist(): void
    {
        $data = $this->getDataWithQuarter();

        $response = $this->actingAs($data['admin'])
            ->post(route('admin.school-year.quarters.auto-generate', $data['schoolYear']));

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    // ========================================================================
    // Model: isCurrent respects manual override
    // ========================================================================

    public function test_is_current_returns_true_for_manually_active_quarter(): void
    {
        $data = $this->getDataWithQuarter();
        $data['quarter']->update(['is_manually_set_active' => true]);

        $this->assertTrue($data['quarter']->isCurrent());
    }

    public function test_is_current_returns_false_for_other_quarters_when_manual_override_exists(): void
    {
        $data = $this->getBaseTestData();

        $quarter1 = $data['schoolYear']->quarters()->create([
            'quarter' => 1,
            'name' => 'First Quarter',
            'start_date' => now()->subDays(30)->toDateString(),
            'end_date' => now()->addDays(30)->toDateString(),
            'is_locked' => false,
            'is_manually_set_active' => false,
        ]);

        $quarter2 = $data['schoolYear']->quarters()->create([
            'quarter' => 2,
            'name' => 'Second Quarter',
            'start_date' => now()->addDays(31)->toDateString(),
            'end_date' => now()->addDays(90)->toDateString(),
            'is_locked' => false,
            'is_manually_set_active' => true,
        ]);

        $this->assertFalse($quarter1->isCurrent());
        $this->assertTrue($quarter2->isCurrent());
    }
}
