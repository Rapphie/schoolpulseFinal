<?php

namespace Tests\Feature\Admin;

use App\Models\Role;
use App\Models\SchoolYear;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class AdminEnrollmentExportRoutesTest extends TestCase
{
    use DatabaseTransactions;

    private User $admin;

    private SchoolYear $schoolYear;

    protected function setUp(): void
    {
        parent::setUp();

        $adminRole = Role::firstOrCreate(
            ['name' => 'admin'],
            ['description' => 'Administrator role']
        );

        $this->admin = User::factory()->create([
            'role_id' => $adminRole->id,
        ]);

        $this->schoolYear = SchoolYear::create([
            'name' => 'SY-EXPORT-TEST',
            'start_date' => '2026-06-01',
            'end_date' => '2027-03-31',
            'is_active' => true,
            'is_promotion_open' => false,
        ]);
    }

    public function test_admin_export_all_enrollees_route_returns_download_response(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.enrollment.exportAll', [
                'school_year_id' => $this->schoolYear->id,
            ]));

        $response->assertStatus(200);
        $this->assertNotNull($response->headers->get('content-disposition'));
        $this->assertStringContainsString('.xlsx', $response->headers->get('content-disposition'));
    }

    public function test_admin_export_mine_enrollees_route_returns_download_response(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.enrollment.exportMine'));

        $response->assertStatus(200);
        $this->assertNotNull($response->headers->get('content-disposition'));
        $this->assertStringContainsString('.xlsx', $response->headers->get('content-disposition'));
    }
}
