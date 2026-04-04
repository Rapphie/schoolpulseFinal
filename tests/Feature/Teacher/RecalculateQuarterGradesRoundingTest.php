<?php

namespace Tests\Feature\Teacher;

use App\Jobs\RecalculateQuarterGradesJob;
use App\Models\Assessment;
use App\Models\AssessmentScore;
use App\Models\Classes;
use App\Models\ClassSubjectWeight;
use App\Models\Enrollment;
use App\Models\GradeLevel;
use App\Models\Role;
use App\Models\SchoolYear;
use App\Models\Section;
use App\Models\Student;
use App\Models\StudentProfile;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use App\Services\AssessmentWeightService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecalculateQuarterGradesRoundingTest extends TestCase
{
    use RefreshDatabase;

    public function test_recalculation_rounds_weighted_components_before_transmutation(): void
    {
        $schoolYear = SchoolYear::create([
            'name' => 'SY-RND-2025',
            'start_date' => now()->startOfYear()->toDateString(),
            'end_date' => now()->endOfYear()->toDateString(),
            'is_active' => true,
            'is_promotion_open' => false,
        ]);

        $gradeLevel = GradeLevel::factory()->create([
            'name' => 'Grade 2',
            'level' => 2,
        ]);

        $teacherRole = Role::firstOrCreate(
            ['name' => 'teacher'],
            ['description' => 'Teacher role for recalculation tests']
        );

        $teacherUser = User::factory()->create(['role_id' => $teacherRole->id]);
        $teacher = Teacher::create([
            'user_id' => $teacherUser->id,
            'phone' => null,
            'gender' => 'other',
            'date_of_birth' => null,
            'address' => null,
            'qualification' => null,
            'status' => 'active',
        ]);

        $section = Section::create([
            'name' => 'RND-SILANG',
            'grade_level_id' => $gradeLevel->id,
        ]);

        $class = Classes::create([
            'section_id' => $section->id,
            'school_year_id' => $schoolYear->id,
            'teacher_id' => $teacher->id,
            'capacity' => 45,
        ]);

        $subject = Subject::factory()->create([
            'grade_level_id' => $gradeLevel->id,
            'name' => 'ENGLISH RND',
        ]);

        ClassSubjectWeight::create([
            'class_id' => $class->id,
            'subject_id' => $subject->id,
            'written_works_weight' => 30,
            'performance_tasks_weight' => 50,
            'quarterly_assessments_weight' => 20,
        ]);

        $student = Student::create([
            'student_id' => 'RND-STUDENT-001',
            'lrn' => null,
            'first_name' => 'Rounding',
            'last_name' => 'Student',
            'gender' => 'female',
            'birthdate' => null,
            'guardian_id' => null,
            'enrollment_date' => now()->toDateString(),
            'address' => null,
            'distance_km' => null,
            'transportation' => null,
            'family_income' => null,
        ]);

        $profile = StudentProfile::create([
            'student_id' => $student->id,
            'school_year_id' => $schoolYear->id,
            'grade_level_id' => $gradeLevel->id,
            'status' => 'enrolled',
            'created_by_teacher_id' => $teacher->id,
        ]);

        Enrollment::create([
            'student_id' => $student->id,
            'class_id' => $class->id,
            'school_year_id' => $schoolYear->id,
            'teacher_id' => $teacher->id,
            'enrolled_by_user_id' => $teacherUser->id,
            'student_profile_id' => $profile->id,
            'enrollment_date' => now()->toDateString(),
            'status' => 'enrolled',
        ]);

        $writtenWork = Assessment::create([
            'class_id' => $class->id,
            'subject_id' => $subject->id,
            'teacher_id' => $teacher->id,
            'school_year_id' => $schoolYear->id,
            'name' => 'Quarter 1 Written Work 1',
            'type' => 'written_works',
            'max_score' => 55,
            'quarter' => 1,
            'assessment_date' => now()->toDateString(),
        ]);

        $performanceTask = Assessment::create([
            'class_id' => $class->id,
            'subject_id' => $subject->id,
            'teacher_id' => $teacher->id,
            'school_year_id' => $schoolYear->id,
            'name' => 'Quarter 1 Performance Task 1',
            'type' => 'performance_tasks',
            'max_score' => 70,
            'quarter' => 1,
            'assessment_date' => now()->toDateString(),
        ]);

        $quarterlyAssessment = Assessment::create([
            'class_id' => $class->id,
            'subject_id' => $subject->id,
            'teacher_id' => $teacher->id,
            'school_year_id' => $schoolYear->id,
            'name' => 'Quarter 1 Quarterly Assessment',
            'type' => 'quarterly_assessments',
            'max_score' => 20,
            'quarter' => 1,
            'assessment_date' => now()->toDateString(),
        ]);

        AssessmentScore::create([
            'assessment_id' => $writtenWork->id,
            'student_id' => $student->id,
            'student_profile_id' => $profile->id,
            'score' => 42,
        ]);

        AssessmentScore::create([
            'assessment_id' => $performanceTask->id,
            'student_id' => $student->id,
            'student_profile_id' => $profile->id,
            'score' => 48,
        ]);

        AssessmentScore::create([
            'assessment_id' => $quarterlyAssessment->id,
            'student_id' => $student->id,
            'student_profile_id' => $profile->id,
            'score' => 14,
        ]);

        $job = new RecalculateQuarterGradesJob(
            $class->id,
            $subject->id,
            1,
            $teacher->id,
            $schoolYear->id,
            [$student->id]
        );

        $job->handle(app(AssessmentWeightService::class));

        $this->assertDatabaseHas('grades', [
            'student_id' => $student->id,
            'subject_id' => $subject->id,
            'teacher_id' => $teacher->id,
            'school_year_id' => $schoolYear->id,
            'quarter' => '1',
            'grade' => 82,
        ]);
    }
}
