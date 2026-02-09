<?php

namespace Tests\Feature\Teacher;

use App\Models\Teacher;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class TempFetchAnalyticsWithFiltersTest extends TestCase
{
    use DatabaseTransactions;

    public function test_fetch_absenteeism_with_class_and_grade_filters()
    {
        // $teacher = Teacher::with(['user', 'classes.section.gradeLevel'])->first();
        // if (! $teacher || ! $teacher->user) {
        //     $this->markTestSkipped('No teacher with linked user found.');
        // }

        // $class = $teacher->classes->first();
        // $grade = optional(optional($class)->section)->gradeLevel;

        // if (! $class || ! $grade) {
        //     $this->markTestSkipped('Teacher does not have a class with a section/grade configured.');
        // }

        // $this->actingAs($teacher->user);

        // $url = route('teacher.analytics.absenteeism') . '?class_id=' . $class->id . '&grade_level_id=' . $grade->id;

        // $response = $this->get($url);

        // $response->assertStatus(200);

        // $content = $response->getContent();
        // $this->assertMatchesRegularExpression('/Student Risk Monitoring|Data Unavailable/', $content);
    }
}
