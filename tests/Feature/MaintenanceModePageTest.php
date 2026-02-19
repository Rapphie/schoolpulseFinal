<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class MaintenanceModePageTest extends TestCase
{
    public function test_maintenance_mode_displays_custom_503_page(): void
    {
        Artisan::call('up');

        try {
            Artisan::call('down');

            $response = $this->get('/login');

            $response->assertStatus(503);
            $response->assertSee('System Maintenance');
            $response->assertSee('Please check back shortly.');
        } finally {
            Artisan::call('up');
        }
    }
}
