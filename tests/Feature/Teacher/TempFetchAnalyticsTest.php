<?php

namespace Tests\Feature\Teacher;

use Tests\TestCase;
use App\Models\Teacher;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class TempFetchAnalyticsTest extends TestCase
{
    use DatabaseTransactions;

    public function test_fetch_absenteeism_page_as_teacher()
    {
        $teacher = Teacher::with('user')->first();
        if (! $teacher || ! $teacher->user) {
            $this->markTestSkipped('No teacher with linked user found in the database.');
        }

        $this->actingAs($teacher->user);

        try {
            $response = $this->get(route('teacher.analytics.absenteeism'));
        } catch (\Throwable $e) {
            $this->fail('Exception when requesting absenteeism page: ' . $e->getMessage());
            return;
        }

        $response->assertStatus(200);
    }
}
