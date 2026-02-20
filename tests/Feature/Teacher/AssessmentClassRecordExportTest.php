<?php

namespace Tests\Feature\Teacher;

use App\Exports\AssessmentClassRecordExport;
use App\Models\Assessment;
use App\Models\AssessmentScore;
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
use Illuminate\Support\Str;
use Tests\TestCase;

class AssessmentClassRecordExportTest extends TestCase
{
    use DatabaseTransactions;

    private User $teacherUser;

    private Teacher $teacher;

    private SchoolYear $activeSchoolYear;

    private Classes $classroom;

    private Subject $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->activeSchoolYear = SchoolYear::where('is_active', true)->first()
            ?? SchoolYear::create([
                'name' => 'SY-EXPORT-'.now()->format('Y'),
                'start_date' => now()->startOfYear()->toDateString(),
                'end_date' => now()->endOfYear()->toDateString(),
                'is_active' => true,
                'is_promotion_open' => false,
            ]);

        $gradeLevel = GradeLevel::query()->orderBy('level')->first()
            ?? GradeLevel::factory()->create(['name' => 'Grade 5', 'level' => 5]);

        $this->teacherUser = User::factory()->create([
            'role_id' => 2,
            'temporary_password' => null,
        ]);

        $this->teacher = Teacher::factory()->create([
            'user_id' => $this->teacherUser->id,
            'status' => 'active',
        ]);

        $section = Section::create([
            'name' => 'EX-'.Str::upper(Str::random(4)),
            'grade_level_id' => $gradeLevel->id,
        ]);

        $this->classroom = Classes::create([
            'section_id' => $section->id,
            'school_year_id' => $this->activeSchoolYear->id,
            'teacher_id' => $this->teacher->id,
            'capacity' => 40,
        ]);

        $this->subject = Subject::factory()->create([
            'grade_level_id' => $gradeLevel->id,
            'name' => 'Export Subject '.Str::upper(Str::random(4)),
        ]);

        Schedule::create([
            'class_id' => $this->classroom->id,
            'subject_id' => $this->subject->id,
            'teacher_id' => $this->teacher->id,
            'day_of_week' => ['Tuesday'],
            'start_time' => '08:00',
            'end_time' => '09:00',
            'room' => 'R-EXP',
        ]);

        $student = Student::create([
            'student_id' => 'EXP-'.now()->timestamp.'-'.random_int(100, 999),
            'lrn' => 'LRN-EXP-'.random_int(10000, 99999),
            'first_name' => 'Erin',
            'last_name' => 'Export',
            'birthdate' => '2012-03-03',
            'gender' => 'female',
            'enrollment_date' => now()->toDateString(),
        ]);

        Enrollment::create([
            'student_id' => $student->id,
            'class_id' => $this->classroom->id,
            'school_year_id' => $this->activeSchoolYear->id,
            'teacher_id' => $this->teacher->id,
            'enrollment_date' => now(),
            'status' => 'enrolled',
        ]);

        $assessment = Assessment::create([
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

        AssessmentScore::create([
            'assessment_id' => $assessment->id,
            'student_id' => $student->id,
            'student_profile_id' => null,
            'score' => 45,
            'remarks' => null,
        ]);
    }

    public function test_quarter_export_route_returns_excel_download(): void
    {
        $response = $this->actingAs($this->teacherUser)
            ->get(route('teacher.assessments.exportClassRecord', [
                'class' => $this->classroom->id,
                'subject_id' => $this->subject->id,
                'quarter' => 1,
            ]));

        $response->assertStatus(200);
        $this->assertNotNull($response->headers->get('content-disposition'));
        $this->assertStringContainsString('.xlsx', (string) $response->headers->get('content-disposition'));
        $this->assertStringContainsString('_q1_', Str::lower((string) $response->headers->get('content-disposition')));
    }

    public function test_full_year_export_route_returns_excel_download(): void
    {
        $response = $this->actingAs($this->teacherUser)
            ->get(route('teacher.assessments.exportClassRecordAll', [
                'class' => $this->classroom->id,
                'subject_id' => $this->subject->id,
            ]));

        $response->assertStatus(200);
        $this->assertNotNull($response->headers->get('content-disposition'));
        $this->assertStringContainsString('.xlsx', (string) $response->headers->get('content-disposition'));
        $this->assertStringContainsString(
            '_all-quarters_',
            Str::lower((string) $response->headers->get('content-disposition'))
        );
    }

    public function test_export_data_includes_gender_groupings_and_scores(): void
    {
        $export = new AssessmentClassRecordExport($this->classroom, $this->subject, $this->teacher->id, 1);
        $data = $export->array();

        $this->assertNotEmpty($data);

        $hasFemaleHeader = false;
        $hasStudent = false;

        foreach ($data as $row) {
            if ($row[1] === 'FEMALE') {
                $hasFemaleHeader = true;
            }
            if ($row[1] === 'Export' && $row[2] === 'Erin') {
                $hasStudent = true;
                $this->assertEquals(45, $row[4]);
            }
        }

        $this->assertTrue($hasFemaleHeader, 'Export data should contain gender headers');
        $this->assertTrue($hasStudent, 'Export data should contain student data');
    }

    public function test_export_start_cell_is_row_11(): void
    {
        $export = new AssessmentClassRecordExport($this->classroom, $this->subject, $this->teacher->id, 1);

        $this->assertEquals('A11', $export->startCell());
    }

    public function test_export_title_is_quarter_prefixed(): void
    {
        $export = new AssessmentClassRecordExport($this->classroom, $this->subject, $this->teacher->id, 1);

        $this->assertEquals('Q1', $export->title());
    }
}
