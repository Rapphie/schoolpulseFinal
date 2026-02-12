<?php

namespace Tests\Feature\Teacher;

use App\Models\Assessment;
use App\Models\Classes;
use App\Models\Enrollment;
use App\Models\GradeLevel;
use App\Models\Schedule;
use App\Models\SchoolYear;
use App\Models\Section;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class AssessmentOralParticipationConsistencyTest extends TestCase
{
    use DatabaseTransactions;

    private User $teacherUser;

    private Teacher $teacher;

    private Classes $class;

    private Subject $subjectA;

    private Subject $subjectB;

    private SchoolYear $activeSchoolYear;

    protected function setUp(): void
    {
        parent::setUp();

        $this->activeSchoolYear = SchoolYear::where('is_active', true)->first();

        $gradeLevel = GradeLevel::first();

        $this->teacherUser = User::factory()->create([
            'role_id' => 2,
            'temporary_password' => null,
        ]);

        $this->teacher = Teacher::factory()->create([
            'user_id' => $this->teacherUser->id,
            'status' => 'active',
        ]);

        $section = Section::create([
            'name' => 'OPTest',
            'grade_level_id' => $gradeLevel->id,
        ]);

        $this->class = Classes::create([
            'section_id' => $section->id,
            'school_year_id' => $this->activeSchoolYear->id,
            'teacher_id' => $this->teacher->id,
            'capacity' => 40,
        ]);

        $this->subjectA = Subject::factory()->create([
            'grade_level_id' => $gradeLevel->id,
            'name' => 'OPTestSubjectA',
        ]);

        $this->subjectB = Subject::factory()->create([
            'grade_level_id' => $gradeLevel->id,
            'name' => 'OPTestSubjectB',
        ]);

        Schedule::create([
            'class_id' => $this->class->id,
            'subject_id' => $this->subjectA->id,
            'teacher_id' => $this->teacher->id,
            'day_of_week' => json_encode(['Monday']),
            'start_time' => '08:00',
            'end_time' => '09:00',
            'room' => 'R1',
        ]);

        Schedule::create([
            'class_id' => $this->class->id,
            'subject_id' => $this->subjectB->id,
            'teacher_id' => $this->teacher->id,
            'day_of_week' => json_encode(['Tuesday']),
            'start_time' => '08:00',
            'end_time' => '09:00',
            'room' => 'R2',
        ]);

        $student = Student::create([
            'student_id' => '2026-OP-'.rand(100, 999),
            'first_name' => 'OPTest',
            'last_name' => 'Student',
            'birthdate' => '2012-01-01',
            'gender' => 'male',
            'enrollment_date' => now(),
        ]);

        Enrollment::create([
            'student_id' => $student->id,
            'class_id' => $this->class->id,
            'school_year_id' => $this->activeSchoolYear->id,
            'teacher_id' => $this->teacher->id,
            'enrollment_date' => now(),
            'status' => 'enrolled',
        ]);
    }

    public function test_op_column_appears_in_all_quarters_for_subject_with_op_data(): void
    {
        Assessment::create([
            'class_id' => $this->class->id,
            'subject_id' => $this->subjectA->id,
            'teacher_id' => $this->teacher->id,
            'school_year_id' => $this->activeSchoolYear->id,
            'name' => 'Oral Participation',
            'type' => 'oral_participation',
            'max_score' => 10,
            'quarter' => 1,
            'assessment_date' => now(),
        ]);

        $response = $this->actingAs($this->teacherUser)
            ->get(route('teacher.assessments.index', [
                'class' => $this->class->id,
                'subject_id' => $this->subjectA->id,
            ]));

        $response->assertStatus(200);

        $content = $response->getContent();

        for ($quarter = 1; $quarter <= 4; $quarter++) {
            $this->assertOralParticipationColumnExists($content, $quarter);
        }
    }

    public function test_op_column_appears_in_all_quarters_for_subject_without_op_data(): void
    {
        $response = $this->actingAs($this->teacherUser)
            ->get(route('teacher.assessments.index', [
                'class' => $this->class->id,
                'subject_id' => $this->subjectB->id,
            ]));

        $response->assertStatus(200);

        $content = $response->getContent();

        for ($quarter = 1; $quarter <= 4; $quarter++) {
            $this->assertOralParticipationColumnExists($content, $quarter);
        }
    }

    public function test_op_column_appears_for_second_subject_when_only_first_subject_has_op(): void
    {
        Assessment::create([
            'class_id' => $this->class->id,
            'subject_id' => $this->subjectA->id,
            'teacher_id' => $this->teacher->id,
            'school_year_id' => $this->activeSchoolYear->id,
            'name' => 'Oral Participation',
            'type' => 'oral_participation',
            'max_score' => 15,
            'quarter' => 1,
            'assessment_date' => now(),
        ]);

        $response = $this->actingAs($this->teacherUser)
            ->get(route('teacher.assessments.index', [
                'class' => $this->class->id,
                'subject_id' => $this->subjectB->id,
            ]));

        $response->assertStatus(200);

        $content = $response->getContent();

        for ($quarter = 1; $quarter <= 4; $quarter++) {
            $this->assertOralParticipationColumnExists($content, $quarter);
        }
    }

    public function test_op_from_another_teacher_is_visible(): void
    {
        $otherUser = User::factory()->create([
            'role_id' => 2,
            'temporary_password' => null,
        ]);

        $otherTeacher = Teacher::factory()->create([
            'user_id' => $otherUser->id,
            'status' => 'active',
        ]);

        Assessment::create([
            'class_id' => $this->class->id,
            'subject_id' => $this->subjectA->id,
            'teacher_id' => $otherTeacher->id,
            'school_year_id' => $this->activeSchoolYear->id,
            'name' => 'Oral Participation - Other Teacher',
            'type' => 'oral_participation',
            'max_score' => 20,
            'quarter' => 2,
            'assessment_date' => now(),
        ]);

        $response = $this->actingAs($this->teacherUser)
            ->get(route('teacher.assessments.index', [
                'class' => $this->class->id,
                'subject_id' => $this->subjectA->id,
            ]));

        $response->assertStatus(200);

        $content = $response->getContent();

        $this->assertOralParticipationColumnExists($content, 2);
    }

    /**
     * Assert that the Oral Participation column header (green "OP" header) exists in a given quarter's tab.
     */
    private function assertOralParticipationColumnExists(string $content, int $quarter): void
    {
        $pattern = '/id="quarter'.$quarter.'".*?oral-participation-header.*?OP/s';
        $this->assertMatchesRegularExpression(
            $pattern,
            $content,
            "Oral Participation column should appear in Quarter {$quarter}"
        );
    }
}
