<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class GlobalModalValidationStateTest extends TestCase
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

    public function test_base_layout_exposes_global_modal_validation_state_after_validation_failure(): void
    {
        $adminUser = $this->getAdminUser();

        $response = $this->actingAs($adminUser)
            ->from(route('admin.dashboard'))
            ->followingRedirects()
            ->post(route('admin.school-year.store'), [
                'start_date' => 'invalid-date',
                'end_date' => '',
            ]);

        $response->assertStatus(200);
        $response->assertSee('window.__modalFormState', false);
        $response->assertSee('oldInputState', false);
        $response->assertSee('errorMessagesState', false);
    }
}
