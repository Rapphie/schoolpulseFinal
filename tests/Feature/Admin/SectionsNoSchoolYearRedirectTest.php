<?php

namespace Tests\Feature\Admin;

use App\Models\SchoolYear;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SectionsNoSchoolYearRedirectTest extends TestCase
{
    use DatabaseTransactions;

    public function test_sections_index_redirects_to_dashboard_when_no_school_year_exists(): void
    {
        $admin = User::where('role_id', 1)->first();
        if (! $admin) {
            $this->markTestSkipped('No admin user found.');
        }

        $admin->forceFill([
            'temporary_password' => null,
            'temporary_password_expires_at' => null,
        ])->save();

        Schema::disableForeignKeyConstraints();
        SchoolYear::query()->delete();
        Schema::enableForeignKeyConstraints();

        $response = $this->actingAs($admin)->get(route('admin.sections.index'));

        $response->assertRedirect(route('admin.dashboard'));
        $response->assertSessionHas('error', 'No school year found. Please create one first in the dashboard.');
    }
}
