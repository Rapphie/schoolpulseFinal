<?php

namespace Tests\Feature\Teacher;

use App\Models\Attendance;
use App\Models\Classes;
use App\Models\Enrollment;
use App\Models\GradeLevel;
use App\Models\Schedule;
use App\Models\SchoolYear;
use App\Models\Section;
use App\Models\Student;
use App\Models\StudentProfile;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Testing\TestResponse;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Tests\TestCase;

class AttendancePatternSf2ExportTest extends TestCase
{
    use DatabaseTransactions;

    protected Teacher $adviser;

    protected Teacher $nonAdviserTeacher;

    protected Section $section;

    protected Classes $class;

    protected SchoolYear $activeSchoolYear;

    protected GradeLevel $gradeLevel;

    protected Subject $subject;

    protected int $studentSequence = 1;

    protected function setUp(): void
    {
        parent::setUp();

        $this->activeSchoolYear = SchoolYear::where('is_active', true)->first()
            ?? SchoolYear::create([
                'name' => '2025-2026',
                'is_active' => true,
                'start_date' => Carbon::parse('2025-06-01'),
                'end_date' => Carbon::parse('2026-03-31'),
            ]);

        $suffix = (string) str()->random(6);

        $this->gradeLevel = GradeLevel::create([
            'name' => 'Grade 7 '.$suffix,
            'level' => 7,
        ]);

        $this->section = Section::create([
            'name' => 'Sapphire',
            'grade_level_id' => $this->gradeLevel->id,
        ]);

        $this->adviser = $this->createTeacher();
        $this->nonAdviserTeacher = $this->createTeacher();

        $this->class = Classes::create([
            'section_id' => $this->section->id,
            'school_year_id' => $this->activeSchoolYear->id,
            'teacher_id' => $this->adviser->id,
            'capacity' => 60,
        ]);

        $this->subject = Subject::create([
            'name' => 'English '.$suffix,
            'code' => 'ENG'.$suffix,
            'grade_level_id' => $this->gradeLevel->id,
        ]);

        Schedule::create([
            'class_id' => $this->class->id,
            'subject_id' => $this->subject->id,
            'teacher_id' => $this->adviser->id,
            'day_of_week' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'],
            'start_time' => '07:30:00',
            'end_time' => '08:30:00',
        ]);
    }

    public function test_adviser_can_download_sf2_xlsx_for_section_and_month(): void
    {
        $maleStudent = $this->createStudent('Doe', 'John', 'male');
        $femaleStudent = $this->createStudent('Smith', 'Jane', 'female');

        $this->createAttendance($maleStudent, '2025-06-03', 'absent');
        $this->createAttendance($femaleStudent, '2025-06-04', 'late');

        $response = $this->actingAs($this->adviser->user)->get(route('teacher.attendance.pattern.export', [
            'section_id' => $this->section->id,
            'month' => '2025-06',
            'school_id' => '123456',
            'school_name' => 'School Pulse High',
        ]));

        $response->assertOk();
        $response->assertDownload('sf2_sapphire_202506.xlsx');

        $sheet = $this->loadSpreadsheetFromResponse($response)->getSheet(0);

        $this->assertSame('123456', (string) $sheet->getCell('C6')->getValue());
        $this->assertSame($this->activeSchoolYear->name, (string) $sheet->getCell('K6')->getValue());
        $this->assertSame('June 2025', (string) $sheet->getCell('X6')->getValue());
        $this->assertSame('School Pulse High', (string) $sheet->getCell('C8')->getValue());
        $this->assertSame($this->gradeLevel->name, (string) $sheet->getCell('X8')->getValue());
        $this->assertSame($this->section->name, (string) $sheet->getCell('AC8')->getValue());

        $this->assertSame('Doe, John', (string) $sheet->getCell('B13')->getValue());
        $this->assertSame('Smith, Jane', (string) $sheet->getCell('B35')->getValue());
        $this->assertSame('X', (string) $sheet->getCell('E13')->getValue());
        $this->assertSame('T', (string) $sheet->getCell('F35')->getValue());
        $this->assertSame(1, $sheet->getCell('AC13')->getValue());
        $this->assertSame(1, $sheet->getCell('AD35')->getValue());
        $this->assertSame(1, $sheet->getCell('E61')->getValue());
        $this->assertSame(1, $sheet->getCell('F34')->getValue());
        $this->assertSame(1, $sheet->getCell('F60')->getValue());
        $this->assertSame(2, $sheet->getCell('F61')->getValue());
    }

    public function test_export_requires_month_and_section(): void
    {
        $response = $this->actingAs($this->adviser->user)
            ->from(route('teacher.attendance.pattern'))
            ->get(route('teacher.attendance.pattern.export', [
                'section_id' => $this->section->id,
            ]));

        $response->assertRedirect(route('teacher.attendance.pattern'));
        $response->assertSessionHasErrors(['month']);

        $response = $this->actingAs($this->adviser->user)
            ->from(route('teacher.attendance.pattern'))
            ->get(route('teacher.attendance.pattern.export', [
                'month' => '2025-06',
            ]));

        $response->assertRedirect(route('teacher.attendance.pattern'));
        $response->assertSessionHasErrors(['section_id']);
    }

    public function test_non_adviser_cannot_export_sf2(): void
    {
        $response = $this->actingAs($this->nonAdviserTeacher->user)
            ->from(route('teacher.attendance.pattern'))
            ->get(route('teacher.attendance.pattern.export', [
                'section_id' => $this->section->id,
                'month' => '2025-06',
            ]));

        $response->assertRedirect(route('teacher.attendance.pattern'));
        $response->assertSessionHas('error');
    }

    public function test_export_creates_multiple_pages_when_male_learners_exceed_male_slots(): void
    {
        for ($index = 1; $index <= 22; $index++) {
            $suffix = str_pad((string) $index, 2, '0', STR_PAD_LEFT);
            $this->createStudent('Male'.$suffix, 'Student'.$suffix, 'male');
        }

        $response = $this->actingAs($this->adviser->user)->get(route('teacher.attendance.pattern.export', [
            'section_id' => $this->section->id,
            'month' => '2025-06',
        ]));

        $response->assertOk();
        $response->assertDownload('sf2_sapphire_202506.xlsx');

        $spreadsheet = $this->loadSpreadsheetFromResponse($response);

        $this->assertSame(2, $spreadsheet->getSheetCount());
        $this->assertSame('Male21, Student21', (string) $spreadsheet->getSheet(0)->getCell('B33')->getValue());
        $this->assertSame('Male22, Student22', (string) $spreadsheet->getSheet(1)->getCell('B13')->getValue());
        $this->assertSame('School Form 2 :  Page 1 of 2', (string) $spreadsheet->getSheet(0)->getCell('A91')->getValue());
        $this->assertSame('School Form 2 :  Page 2 of 2', (string) $spreadsheet->getSheet(1)->getCell('A91')->getValue());
    }

    public function test_summary_cells_are_populated_for_enrollment_and_profile_totals(): void
    {
        $dropoutStudent = $this->createStudent('Dropout', 'Mark', 'male');
        $transferOutStudent = $this->createStudent('Transferout', 'Anne', 'female');
        $transferInStudent = $this->createStudent('Transferin', 'Paul', 'male', '2025-06-10', 'transferred');

        StudentProfile::create([
            'student_id' => $dropoutStudent->id,
            'school_year_id' => $this->activeSchoolYear->id,
            'grade_level_id' => $this->gradeLevel->id,
            'status' => 'dropped',
        ]);

        StudentProfile::create([
            'student_id' => $transferOutStudent->id,
            'school_year_id' => $this->activeSchoolYear->id,
            'grade_level_id' => $this->gradeLevel->id,
            'status' => 'transferred',
        ]);

        foreach (range(2, 6) as $day) {
            $this->createAttendance($dropoutStudent, "2025-06-{$day}", 'absent');
        }

        $response = $this->actingAs($this->adviser->user)->get(route('teacher.attendance.pattern.export', [
            'section_id' => $this->section->id,
            'month' => '2025-06',
        ]));

        $response->assertOk();

        $sheet = $this->loadSpreadsheetFromResponse($response)->getSheet(0);

        $this->assertSame('June 2025', (string) $sheet->getCell('AC63')->getValue());
        $this->assertSame(21, $sheet->getCell('AG63')->getValue());
        $this->assertSame(2, $sheet->getCell('AJ65')->getValue());
        $this->assertSame(1, $sheet->getCell('AJ67')->getValue());
        $this->assertSame(3, $sheet->getCell('AJ69')->getValue());
        $this->assertSame(1, $sheet->getCell('AJ77')->getValue());
        $this->assertSame(1, $sheet->getCell('AH79')->getValue());
        $this->assertSame(1, $sheet->getCell('AJ79')->getValue());
        $this->assertSame(1, $sheet->getCell('AI81')->getValue());
        $this->assertSame(1, $sheet->getCell('AJ81')->getValue());
        $this->assertSame(1, $sheet->getCell('AH83')->getValue());
        $this->assertSame(1, $sheet->getCell('AJ83')->getValue());
    }

    public function test_daily_status_is_present_when_not_all_subjects_are_absent(): void
    {
        $suffix = (string) str()->random(6);

        $subject2 = Subject::create([
            'name' => 'Math '.$suffix,
            'code' => 'MTH'.$suffix,
            'grade_level_id' => $this->gradeLevel->id,
        ]);

        Schedule::create([
            'class_id' => $this->class->id,
            'subject_id' => $subject2->id,
            'teacher_id' => $this->adviser->id,
            'day_of_week' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'],
            'start_time' => '09:00:00',
            'end_time' => '10:00:00',
        ]);

        $student = $this->createStudent('Cruz', 'Maria', 'female');

        $this->createAttendanceForSubject($student, '2025-06-03', 'absent', $this->subject);
        $this->createAttendanceForSubject($student, '2025-06-03', 'present', $subject2);

        $response = $this->actingAs($this->adviser->user)->get(route('teacher.attendance.pattern.export', [
            'section_id' => $this->section->id,
            'month' => '2025-06',
        ]));

        $response->assertOk();

        $sheet = $this->loadSpreadsheetFromResponse($response)->getSheet(0);

        $this->assertSame('Cruz, Maria', (string) $sheet->getCell('B35')->getValue());
        $this->assertEmpty($sheet->getCell('E35')->getValue(), 'Day should not show X when not all subjects absent');
        $this->assertEmpty($sheet->getCell('AC35')->getValue(), 'Absent total should be blank');
    }

    public function test_daily_status_is_absent_when_all_subjects_are_absent(): void
    {
        $suffix = (string) str()->random(6);

        $subject2 = Subject::create([
            'name' => 'Math '.$suffix,
            'code' => 'MTH'.$suffix,
            'grade_level_id' => $this->gradeLevel->id,
        ]);

        Schedule::create([
            'class_id' => $this->class->id,
            'subject_id' => $subject2->id,
            'teacher_id' => $this->adviser->id,
            'day_of_week' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'],
            'start_time' => '09:00:00',
            'end_time' => '10:00:00',
        ]);

        $student = $this->createStudent('Santos', 'Pedro', 'male');

        $this->createAttendanceForSubject($student, '2025-06-03', 'absent', $this->subject);
        $this->createAttendanceForSubject($student, '2025-06-03', 'absent', $subject2);

        $response = $this->actingAs($this->adviser->user)->get(route('teacher.attendance.pattern.export', [
            'section_id' => $this->section->id,
            'month' => '2025-06',
        ]));

        $response->assertOk();

        $sheet = $this->loadSpreadsheetFromResponse($response)->getSheet(0);

        $this->assertSame('Santos, Pedro', (string) $sheet->getCell('B13')->getValue());
        $this->assertSame('X', (string) $sheet->getCell('E13')->getValue(), 'Day should show X when all subjects absent');
        $this->assertSame(1, $sheet->getCell('AC13')->getValue(), 'Absent total should be 1');
    }

    public function test_daily_status_is_late_when_first_subject_is_late(): void
    {
        $suffix = (string) str()->random(6);

        $subject2 = Subject::create([
            'name' => 'Math '.$suffix,
            'code' => 'MTH'.$suffix,
            'grade_level_id' => $this->gradeLevel->id,
        ]);

        Schedule::create([
            'class_id' => $this->class->id,
            'subject_id' => $subject2->id,
            'teacher_id' => $this->adviser->id,
            'day_of_week' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'],
            'start_time' => '09:00:00',
            'end_time' => '10:00:00',
        ]);

        $student = $this->createStudent('Reyes', 'Ana', 'female');

        $this->createAttendanceForSubject($student, '2025-06-03', 'late', $this->subject);
        $this->createAttendanceForSubject($student, '2025-06-03', 'present', $subject2);

        $response = $this->actingAs($this->adviser->user)->get(route('teacher.attendance.pattern.export', [
            'section_id' => $this->section->id,
            'month' => '2025-06',
        ]));

        $response->assertOk();

        $sheet = $this->loadSpreadsheetFromResponse($response)->getSheet(0);

        $this->assertSame('Reyes, Ana', (string) $sheet->getCell('B35')->getValue());
        $this->assertSame('T', (string) $sheet->getCell('E35')->getValue(), 'Day should show T when first subject is late');
        $this->assertSame(1, $sheet->getCell('AD35')->getValue(), 'Tardy total should be 1');
    }

    public function test_daily_status_is_present_when_late_in_non_first_subject(): void
    {
        $suffix = (string) str()->random(6);

        $subject2 = Subject::create([
            'name' => 'Math '.$suffix,
            'code' => 'MTH'.$suffix,
            'grade_level_id' => $this->gradeLevel->id,
        ]);

        Schedule::create([
            'class_id' => $this->class->id,
            'subject_id' => $subject2->id,
            'teacher_id' => $this->adviser->id,
            'day_of_week' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'],
            'start_time' => '09:00:00',
            'end_time' => '10:00:00',
        ]);

        $student = $this->createStudent('Garcia', 'Luis', 'male');

        $this->createAttendanceForSubject($student, '2025-06-03', 'present', $this->subject);
        $this->createAttendanceForSubject($student, '2025-06-03', 'late', $subject2);

        $response = $this->actingAs($this->adviser->user)->get(route('teacher.attendance.pattern.export', [
            'section_id' => $this->section->id,
            'month' => '2025-06',
        ]));

        $response->assertOk();

        $sheet = $this->loadSpreadsheetFromResponse($response)->getSheet(0);

        $this->assertSame('Garcia, Luis', (string) $sheet->getCell('B13')->getValue());
        $this->assertEmpty($sheet->getCell('E13')->getValue(), 'Day should not show T when late in non-first subject');
        $this->assertEmpty($sheet->getCell('AC13')->getValue(), 'Absent total should be blank');
        $this->assertEmpty($sheet->getCell('AD13')->getValue(), 'Tardy total should be blank');
    }

    private function createTeacher(): Teacher
    {
        $user = User::factory()->teacher()->create();

        return Teacher::factory()->active()->create([
            'user_id' => $user->id,
        ]);
    }

    private function createStudent(
        string $lastName,
        string $firstName,
        string $gender,
        string $enrollmentDate = '2025-06-01',
        string $enrollmentStatus = 'enrolled'
    ): Student {
        $sequence = str_pad((string) $this->studentSequence, 3, '0', STR_PAD_LEFT);

        $student = Student::create([
            'student_id' => 'SF2-'.$sequence,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'gender' => $gender,
            'birthdate' => Carbon::parse('2012-01-01'),
        ]);

        Enrollment::create([
            'student_id' => $student->id,
            'class_id' => $this->class->id,
            'school_year_id' => $this->activeSchoolYear->id,
            'teacher_id' => $this->adviser->id,
            'enrollment_date' => Carbon::parse($enrollmentDate),
            'status' => $enrollmentStatus,
        ]);

        $this->studentSequence++;

        return $student;
    }

    private function createAttendance(Student $student, string $date, string $status): void
    {
        Attendance::create([
            'student_id' => $student->id,
            'class_id' => $this->class->id,
            'subject_id' => $this->subject->id,
            'teacher_id' => $this->adviser->id,
            'status' => $status,
            'date' => Carbon::parse($date),
            'quarter' => 1,
            'school_year_id' => $this->activeSchoolYear->id,
        ]);
    }

    private function createAttendanceForSubject(Student $student, string $date, string $status, Subject $subject): void
    {
        Attendance::create([
            'student_id' => $student->id,
            'class_id' => $this->class->id,
            'subject_id' => $subject->id,
            'teacher_id' => $this->adviser->id,
            'status' => $status,
            'date' => Carbon::parse($date),
            'quarter' => 1,
            'school_year_id' => $this->activeSchoolYear->id,
        ]);
    }

    private function loadSpreadsheetFromResponse(TestResponse $response): Spreadsheet
    {
        $baseResponse = $response->baseResponse;

        $this->assertInstanceOf(BinaryFileResponse::class, $baseResponse);

        return IOFactory::load($baseResponse->getFile()->getPathname());
    }
}
