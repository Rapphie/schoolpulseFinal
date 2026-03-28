<?php

namespace Tests\Feature\Teacher;

use App\Models\Assessment;
use App\Models\AssessmentScore;
use App\Models\Classes;
use App\Models\Enrollment;
use App\Models\GradeLevel;
use App\Models\Schedule;
use App\Models\SchoolYear;
use App\Models\SchoolYearQuarter;
use App\Models\Section;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\TestCase;

class AssessmentSaveGradesTest extends TestCase
{
    use DatabaseTransactions;

    private User $teacherUser;

    private Teacher $teacher;

    private SchoolYear $activeSchoolYear;

    private Classes $classroom;

    private Subject $subject;

    private Assessment $assessment;

    private Student $studentA;

    private Student $studentB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class);

        SchoolYearQuarter::query()->update(['is_manually_set_active' => false]);
        SchoolYear::query()->update(['is_active' => false]);

        $this->activeSchoolYear = SchoolYear::create([
            'name' => 'SY-SAVE-'.Str::upper(Str::random(6)),
            'start_date' => now()->startOfYear()->toDateString(),
            'end_date' => now()->endOfYear()->toDateString(),
            'is_active' => true,
            'is_promotion_open' => false,
        ]);

        SchoolYearQuarter::create([
            'school_year_id' => $this->activeSchoolYear->id,
            'quarter' => 1,
            'name' => 'First Quarter',
            'start_date' => now()->subDays(7)->toDateString(),
            'end_date' => now()->addDays(7)->toDateString(),
            'is_locked' => false,
            'is_manually_set_active' => true,
        ]);

        $gradeLevel = GradeLevel::query()->orderBy('level')->first()
            ?? GradeLevel::factory()->create(['name' => 'Grade 4', 'level' => 4]);

        $this->teacherUser = User::factory()->create([
            'role_id' => 2,
            'temporary_password' => null,
        ]);

        $this->teacher = Teacher::factory()->create([
            'user_id' => $this->teacherUser->id,
            'status' => 'active',
        ]);

        $section = Section::create([
            'name' => 'SG-'.Str::upper(Str::random(4)),
            'grade_level_id' => $gradeLevel->id,
        ]);

        $this->classroom = Classes::create([
            'section_id' => $section->id,
            'school_year_id' => $this->activeSchoolYear->id,
            'teacher_id' => $this->teacher->id,
            'capacity' => 45,
        ]);

        $this->subject = Subject::factory()->create([
            'grade_level_id' => $gradeLevel->id,
            'name' => 'Save Grades Subject '.Str::upper(Str::random(4)),
        ]);

        Schedule::create([
            'class_id' => $this->classroom->id,
            'subject_id' => $this->subject->id,
            'teacher_id' => $this->teacher->id,
            'day_of_week' => ['Monday'],
            'start_time' => '08:00',
            'end_time' => '09:00',
            'room' => 'R-SAVE',
        ]);

        $this->studentA = Student::create([
            'student_id' => 'SGA-'.now()->timestamp.'-'.random_int(100, 999),
            'lrn' => 'LRN-SGA-'.random_int(10000, 99999),
            'first_name' => 'Alice',
            'last_name' => 'Saver',
            'birthdate' => '2012-01-01',
            'gender' => 'female',
            'enrollment_date' => now()->toDateString(),
        ]);

        $this->studentB = Student::create([
            'student_id' => 'SGB-'.now()->timestamp.'-'.random_int(100, 999),
            'lrn' => 'LRN-SGB-'.random_int(10000, 99999),
            'first_name' => 'Brian',
            'last_name' => 'Clear',
            'birthdate' => '2012-02-02',
            'gender' => 'male',
            'enrollment_date' => now()->toDateString(),
        ]);

        Enrollment::create([
            'student_id' => $this->studentA->id,
            'class_id' => $this->classroom->id,
            'school_year_id' => $this->activeSchoolYear->id,
            'teacher_id' => $this->teacher->id,
            'enrollment_date' => now(),
            'status' => 'enrolled',
        ]);

        Enrollment::create([
            'student_id' => $this->studentB->id,
            'class_id' => $this->classroom->id,
            'school_year_id' => $this->activeSchoolYear->id,
            'teacher_id' => $this->teacher->id,
            'enrollment_date' => now(),
            'status' => 'enrolled',
        ]);

        $this->assessment = Assessment::create([
            'class_id' => $this->classroom->id,
            'subject_id' => $this->subject->id,
            'teacher_id' => $this->teacher->id,
            'school_year_id' => $this->activeSchoolYear->id,
            'name' => 'Quarter 1 Written Work 1',
            'type' => 'written_works',
            'max_score' => 50,
            'quarter' => 1,
            'assessment_date' => now()->toDateString(),
        ]);
    }

    public function test_teacher_can_save_and_clear_scores_in_single_request(): void
    {
        AssessmentScore::create([
            'assessment_id' => $this->assessment->id,
            'student_id' => $this->studentB->id,
            'student_profile_id' => null,
            'score' => 15,
            'remarks' => null,
        ]);

        $payload = [
            'grades' => [
                [
                    'student_id' => $this->studentA->id,
                    'assessment_id' => $this->assessment->id,
                    'score' => 20,
                ],
                [
                    'student_id' => $this->studentB->id,
                    'assessment_id' => $this->assessment->id,
                    'score' => null,
                ],
            ],
        ];

        $response = $this->actingAs($this->teacherUser)
            ->postJson(route('teacher.assessments.saveGrades', ['class' => $this->classroom->id]), $payload);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('saved_count', 1)
            ->assertJsonPath('cleared_count', 1);

        $this->assertDatabaseHas('assessment_scores', [
            'assessment_id' => $this->assessment->id,
            'student_id' => $this->studentA->id,
            'score' => 20,
        ]);

        $this->assertDatabaseMissing('assessment_scores', [
            'assessment_id' => $this->assessment->id,
            'student_id' => $this->studentB->id,
        ]);

        $this->assertDatabaseHas('grades', [
            'student_id' => $this->studentA->id,
            'subject_id' => $this->subject->id,
            'teacher_id' => $this->teacher->id,
            'school_year_id' => $this->activeSchoolYear->id,
            'quarter' => '1',
            'grade' => 64,
        ]);

        $this->assertDatabaseHas('grades', [
            'student_id' => $this->studentB->id,
            'subject_id' => $this->subject->id,
            'teacher_id' => $this->teacher->id,
            'school_year_id' => $this->activeSchoolYear->id,
            'quarter' => '1',
            'grade' => 60,
        ]);
    }

    public function test_save_grades_reports_rejected_cell_when_score_above_max_score(): void
    {
        $payload = [
            'grades' => [
                [
                    'student_id' => $this->studentA->id,
                    'assessment_id' => $this->assessment->id,
                    'score' => 51,
                ],
            ],
        ];

        $response = $this->actingAs($this->teacherUser)
            ->postJson(route('teacher.assessments.saveGrades', ['class' => $this->classroom->id]), $payload);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('saved_count', 0)
            ->assertJsonPath('cleared_count', 0)
            ->assertJsonPath('rejected_count', 1);

        $this->assertSame($this->studentA->id, (int) $response->json('rejected_cells.0.student_id'));
        $this->assertSame($this->assessment->id, (int) $response->json('rejected_cells.0.assessment_id'));
        $this->assertStringContainsString(
            'exceeds the maximum allowed score',
            (string) $response->json('rejected_cells.0.reason')
        );

        $this->assertDatabaseMissing('assessment_scores', [
            'assessment_id' => $this->assessment->id,
            'student_id' => $this->studentA->id,
        ]);
    }

    public function test_save_grades_handles_mixed_saved_cleared_and_rejected_rows(): void
    {
        AssessmentScore::create([
            'assessment_id' => $this->assessment->id,
            'student_id' => $this->studentB->id,
            'student_profile_id' => null,
            'score' => 15,
            'remarks' => null,
        ]);

        $rejectAssessment = Assessment::create([
            'class_id' => $this->classroom->id,
            'subject_id' => $this->subject->id,
            'teacher_id' => $this->teacher->id,
            'school_year_id' => $this->activeSchoolYear->id,
            'name' => 'Quarter 1 Written Work 2',
            'type' => 'written_works',
            'max_score' => 10,
            'quarter' => 1,
            'assessment_date' => now()->toDateString(),
        ]);

        $payload = [
            'grades' => [
                [
                    'student_id' => $this->studentA->id,
                    'assessment_id' => $this->assessment->id,
                    'score' => 20,
                ],
                [
                    'student_id' => $this->studentB->id,
                    'assessment_id' => $this->assessment->id,
                    'score' => null,
                ],
                [
                    'student_id' => $this->studentA->id,
                    'assessment_id' => $rejectAssessment->id,
                    'score' => 15,
                ],
            ],
        ];

        $response = $this->actingAs($this->teacherUser)
            ->postJson(route('teacher.assessments.saveGrades', ['class' => $this->classroom->id]), $payload);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('saved_count', 1)
            ->assertJsonPath('cleared_count', 1)
            ->assertJsonPath('rejected_count', 1);

        $this->assertDatabaseHas('assessment_scores', [
            'assessment_id' => $this->assessment->id,
            'student_id' => $this->studentA->id,
            'score' => 20,
        ]);

        $this->assertDatabaseMissing('assessment_scores', [
            'assessment_id' => $this->assessment->id,
            'student_id' => $this->studentB->id,
        ]);

        $this->assertDatabaseMissing('assessment_scores', [
            'assessment_id' => $rejectAssessment->id,
            'student_id' => $this->studentA->id,
        ]);
    }

    public function test_clearing_score_deletes_profile_based_record_without_affecting_other_students(): void
    {
        $profileA = $this->studentA->profileFor($this->activeSchoolYear->id);
        $profileB = $this->studentB->profileFor($this->activeSchoolYear->id);

        AssessmentScore::create([
            'assessment_id' => $this->assessment->id,
            'student_id' => $this->studentA->id,
            'student_profile_id' => $profileA?->id,
            'score' => 30,
            'remarks' => null,
        ]);

        AssessmentScore::create([
            'assessment_id' => $this->assessment->id,
            'student_id' => $this->studentB->id,
            'student_profile_id' => $profileB?->id,
            'score' => 25,
            'remarks' => null,
        ]);

        $payload = [
            'grades' => [
                [
                    'student_id' => $this->studentA->id,
                    'assessment_id' => $this->assessment->id,
                    'score' => null,
                ],
            ],
        ];

        $response = $this->actingAs($this->teacherUser)
            ->postJson(route('teacher.assessments.saveGrades', ['class' => $this->classroom->id]), $payload);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('cleared_count', 1);

        $this->assertDatabaseMissing('assessment_scores', [
            'assessment_id' => $this->assessment->id,
            'student_id' => $this->studentA->id,
        ]);

        $this->assertDatabaseHas('assessment_scores', [
            'assessment_id' => $this->assessment->id,
            'student_id' => $this->studentB->id,
            'score' => 25,
        ]);
    }
}
