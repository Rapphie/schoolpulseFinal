<?php

namespace Tests\Feature\Admin;

use App\Jobs\RecalculateQuarterGradesJob;
use App\Models\Assessment;
use App\Models\Classes;
use App\Models\GradeLevel;
use App\Models\GradeLevelSubject;
use App\Models\SchoolYear;
use App\Models\SchoolYearQuarter;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class SettingControllerAssessmentWeightsTest extends TestCase
{
    use RefreshDatabase;

    public function test_updating_assessment_weights_dispatches_recalculate_job_for_unlocked_quarters()
    {
        Queue::fake();

        \App\Models\Role::create(['id' => 1, 'name' => 'admin', 'description' => 'Admin']);
        \App\Models\Role::create(['id' => 2, 'name' => 'teacher', 'description' => 'Teacher']);
        $admin = User::factory()->create(['role_id' => 1]);

        $schoolYear = SchoolYear::create([
            'name' => '2025-2026',
            'start_date' => '2025-06-01',
            'end_date' => '2026-03-31',
            'is_active' => true,
        ]);

        $quarter1 = SchoolYearQuarter::create([
            'school_year_id' => $schoolYear->id,
            'quarter' => 1,
            'name' => 'Q1',
            'start_date' => '2025-06-01',
            'end_date' => '2025-08-14',
            'is_locked' => true, // locked!
        ]);

        $quarter2 = SchoolYearQuarter::create([
            'school_year_id' => $schoolYear->id,
            'quarter' => 2,
            'name' => 'Q2',
            'start_date' => '2025-08-15',
            'end_date' => '2025-10-28',
            'is_locked' => false, // unlocked!
        ]);

        $gradeLevel = GradeLevel::create(['name' => 'Grade 1', 'level' => 1]);
        $subject = Subject::create(['name' => 'Math', 'code' => 'MATH1', 'grade_level_id' => $gradeLevel->id, 'is_active' => true]);

        $section = \App\Models\Section::create(['name' => 'A', 'grade_level_id' => $gradeLevel->id]);

        $gls = GradeLevelSubject::create([
            'grade_level_id' => $gradeLevel->id,
            'subject_id' => $subject->id,
            'is_active' => true,
            'written_works_weight' => 40,
            'performance_tasks_weight' => 40,
            'quarterly_assessments_weight' => 20,
        ]);

        $teacher = Teacher::factory()->create();
        $class = Classes::create([
            'school_year_id' => $schoolYear->id,
            'section_id' => $section->id,
            'teacher_id' => $teacher->id,
            'capacity' => 40,
        ]);

        // Create an assessment in Q1 (locked)
        Assessment::create([
            'class_id' => $class->id,
            'subject_id' => $subject->id,
            'teacher_id' => $teacher->id,
            'school_year_id' => $schoolYear->id,
            'name' => 'Q1 Quiz',
            'type' => 'written_works',
            'max_score' => 10,
            'quarter' => 1,
            'assessment_date' => '2025-07-01',
        ]);

        // Create an assessment in Q2 (unlocked)
        Assessment::create([
            'class_id' => $class->id,
            'subject_id' => $subject->id,
            'teacher_id' => $teacher->id,
            'school_year_id' => $schoolYear->id,
            'name' => 'Q2 Quiz',
            'type' => 'written_works',
            'max_score' => 10,
            'quarter' => 2,
            'assessment_date' => '2025-09-01',
        ]);

        $response = $this->withoutMiddleware()->actingAs($admin)->post(route('admin.settings.update'), [
            'panel' => 'assessment_weights',
            'weights' => [
                $gls->id => [
                    'written_works_weight' => 50,
                    'performance_tasks_weight' => 30,
                    'quarterly_assessments_weight' => 20,
                ],
            ],
        ]);

        $response->assertStatus(302);
        // $response->assertRedirect(route('admin.settings.index', ['panel' => 'assessment_weights']));
        // $response->assertSessionHasNoErrors();

        // Verify DB update
        $this->assertDatabaseHas('grade_level_subjects', [
            'id' => $gls->id,
            'written_works_weight' => 50,
            'performance_tasks_weight' => 30,
        ]);

        // Assert job dispatched for BOTH quarters (even locked ones)
        Queue::assertPushed(RecalculateQuarterGradesJob::class, function ($job) use ($class, $subject, $teacher, $schoolYear) {
            return $job->classId === $class->id
                && $job->subjectId === $subject->id
                && $job->quarter === 1
                && $job->teacherId === $teacher->id
                && $job->schoolYearId === $schoolYear->id;
        });

        Queue::assertPushed(RecalculateQuarterGradesJob::class, function ($job) use ($class, $subject, $teacher, $schoolYear) {
            return $job->classId === $class->id
                && $job->subjectId === $subject->id
                && $job->quarter === 2
                && $job->teacherId === $teacher->id
                && $job->schoolYearId === $schoolYear->id;
        });
    }
}
