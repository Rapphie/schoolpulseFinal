<?php

namespace Tests\Feature\Admin;

use App\Models\GradeLevel;
use App\Models\GradeLevelSubject;
use App\Models\SchoolYear;
use App\Models\SchoolYearMonthDay;
use App\Models\Subject;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class BackfillSchoolYearSettingsCommandTest extends TestCase
{
    use DatabaseTransactions;

    public function test_command_warns_when_active_school_year_is_missing(): void
    {
        $this->artisan('settings:backfill-active-school-year')
            ->expectsOutputToContain('No active school year found.')
            ->assertExitCode(1);
    }

    public function test_command_backfills_missing_month_days_and_missing_weights_only(): void
    {
        $schoolYear = SchoolYear::create([
            'name' => '2099-2100',
            'start_date' => '2099-06-01',
            'end_date' => '2099-08-31',
            'is_active' => true,
            'is_promotion_open' => false,
        ]);

        SchoolYearMonthDay::create([
            'school_year_id' => $schoolYear->id,
            'month' => 6,
            'school_days' => 99,
        ]);

        $gradeLevel = GradeLevel::create([
            'name' => 'Grade 7',
            'level' => 7,
            'description' => 'Backfill command test grade level',
        ]);

        $subjectA = Subject::create([
            'grade_level_id' => $gradeLevel->id,
            'name' => 'Mathematics',
            'code' => 'MATH7',
            'description' => 'Backfill command test subject A',
            'is_active' => true,
        ]);

        $subjectB = Subject::create([
            'grade_level_id' => $gradeLevel->id,
            'name' => 'Science',
            'code' => 'SCI7',
            'description' => 'Backfill command test subject B',
            'is_active' => true,
        ]);

        $configuredRow = GradeLevelSubject::create([
            'grade_level_id' => $gradeLevel->id,
            'subject_id' => $subjectA->id,
            'is_active' => true,
            'written_works_weight' => 30,
            'performance_tasks_weight' => 40,
            'quarterly_assessments_weight' => 30,
        ]);

        $missingRow = GradeLevelSubject::create([
            'grade_level_id' => $gradeLevel->id,
            'subject_id' => $subjectB->id,
            'is_active' => true,
            'written_works_weight' => 0,
            'performance_tasks_weight' => 0,
            'quarterly_assessments_weight' => 0,
        ]);

        $this->artisan('settings:backfill-active-school-year')
            ->expectsOutputToContain('Backfill completed.')
            ->assertExitCode(0);

        $this->assertDatabaseHas('school_year_month_days', [
            'school_year_id' => $schoolYear->id,
            'month' => 6,
            'school_days' => 99,
        ]);

        $this->assertDatabaseHas('school_year_month_days', [
            'school_year_id' => $schoolYear->id,
            'month' => 7,
            'school_days' => 23,
        ]);

        $this->assertDatabaseHas('school_year_month_days', [
            'school_year_id' => $schoolYear->id,
            'month' => 8,
            'school_days' => 20,
        ]);

        $this->assertDatabaseHas('grade_level_subjects', [
            'id' => $configuredRow->id,
            'written_works_weight' => 30,
            'performance_tasks_weight' => 40,
            'quarterly_assessments_weight' => 30,
        ]);

        $this->assertDatabaseHas('grade_level_subjects', [
            'id' => $missingRow->id,
            'written_works_weight' => 40,
            'performance_tasks_weight' => 40,
            'quarterly_assessments_weight' => 20,
        ]);
    }
}
