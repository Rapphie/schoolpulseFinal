<?php

namespace Tests\Feature\Teacher;

use App\Models\Attendance;
use App\Models\Classes;
use App\Models\Enrollment;
use App\Models\GradeLevel;
use App\Models\Role;
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

class TeacherAccessHardeningTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        SchoolYear::query()->update(['is_active' => false]);
        SchoolYearQuarter::query()->update(['is_manually_set_active' => false]);
        $this->ensureTeacherRole();
    }

    public function test_grades_list_only_shows_classes_with_handled_subjects_and_has_no_browser_error_logging(): void
    {
        $suffix = Str::lower(Str::random(8));
        $schoolYear = $this->createActiveSchoolYear($suffix);
        $gradeLevel = $this->createGradeLevel($suffix, 5);
        $sectionHandled = $this->createSection('HANDLED', $suffix, $gradeLevel);
        $sectionAdvisoryOnly = $this->createSection('ADVONLY', $suffix, $gradeLevel);

        [$teacherUser, $teacher] = $this->createTeacherUser();

        $handledClass = Classes::create([
            'section_id' => $sectionHandled->id,
            'school_year_id' => $schoolYear->id,
            'teacher_id' => $teacher->id,
            'capacity' => 30,
        ]);
        $advisoryOnlyClass = Classes::create([
            'section_id' => $sectionAdvisoryOnly->id,
            'school_year_id' => $schoolYear->id,
            'teacher_id' => $teacher->id,
            'capacity' => 30,
        ]);

        $subject = $this->createSubject($gradeLevel, $suffix);
        Schedule::create([
            'class_id' => $handledClass->id,
            'subject_id' => $subject->id,
            'teacher_id' => $teacher->id,
            'day_of_week' => ['monday'],
            'start_time' => '08:00',
            'end_time' => '09:00',
            'room' => 'R1',
        ]);

        $response = $this->actingAs($teacherUser)->get(route('teacher.assessments.list'));

        $response->assertOk();
        $response->assertSee($sectionHandled->name);
        $response->assertDontSee($sectionAdvisoryOnly->name);
        $response->assertDontSee("console.error('Error fetching subjects:', error);", false);
    }

    public function test_grades_index_redirects_with_error_when_teacher_has_no_handled_subject_for_class(): void
    {
        $suffix = Str::lower(Str::random(8));
        $schoolYear = $this->createActiveSchoolYear($suffix);
        $gradeLevel = $this->createGradeLevel($suffix, 4);
        $section = $this->createSection('DIRECT', $suffix, $gradeLevel);
        [$teacherUser, $teacher] = $this->createTeacherUser();

        $class = Classes::create([
            'section_id' => $section->id,
            'school_year_id' => $schoolYear->id,
            'teacher_id' => $teacher->id,
            'capacity' => 30,
        ]);

        $response = $this->actingAs($teacherUser)->get(route('teacher.assessments.index', ['class' => $class->id]));

        $response->assertRedirect(route('teacher.assessments.list'));
        $response->assertSessionHas('error', 'You do not handle any subject for this class in the active school year.');
    }

    public function test_attendance_blocks_non_adviser_all_subject_requests(): void
    {
        $suffix = Str::lower(Str::random(8));
        $schoolYear = $this->createActiveSchoolYear($suffix);
        $gradeLevel = $this->createGradeLevel($suffix, 5);
        $section = $this->createSection('NONADV', $suffix, $gradeLevel);

        [$teacherUser, $teacher] = $this->createTeacherUser();
        [, $adviser] = $this->createTeacherUser();

        $class = Classes::create([
            'section_id' => $section->id,
            'school_year_id' => $schoolYear->id,
            'teacher_id' => $adviser->id,
            'capacity' => 30,
        ]);

        $subject = $this->createSubject($gradeLevel, $suffix);
        Schedule::create([
            'class_id' => $class->id,
            'subject_id' => $subject->id,
            'teacher_id' => $teacher->id,
            'day_of_week' => ['monday'],
            'start_time' => '08:00',
            'end_time' => '09:00',
            'room' => 'R1',
        ]);

        $student = $this->createStudent($suffix);
        $this->enrollStudent($student, $class, $schoolYear, $teacher);

        $getResponse = $this->actingAs($teacherUser)->getJson(route('teacher.attendance.get-students', [
            'section_id' => $section->id,
            'subject_id' => 'all',
            'date' => now()->toDateString(),
        ]));

        $getResponse->assertStatus(422);
        $getResponse->assertJsonValidationErrors(['subject_id']);

        $saveResponse = $this->actingAs($teacherUser)->postJson(route('teacher.attendance.save'), [
            'section_id' => $section->id,
            'subject_id' => 'all',
            'date' => now()->toDateString(),
            'quarter' => '1st Quarter',
            'status' => [
                $student->id => 'present',
            ],
            'remarks' => [],
        ]);

        $saveResponse->assertStatus(422);
        $saveResponse->assertJsonValidationErrors(['subject_id']);
    }

    public function test_attendance_page_renders_section_metadata_for_adviser_toggle_rules(): void
    {
        $suffix = Str::lower(Str::random(8));
        $schoolYear = $this->createActiveSchoolYear($suffix);
        $gradeLevel = $this->createGradeLevel($suffix, 4);
        $adviserSection = $this->createSection('METAADV', $suffix, $gradeLevel);
        $nonAdviserSection = $this->createSection('METANON', $suffix, $gradeLevel);

        [$teacherUser, $teacher] = $this->createTeacherUser();
        [, $otherAdviser] = $this->createTeacherUser();

        $adviserClass = Classes::create([
            'section_id' => $adviserSection->id,
            'school_year_id' => $schoolYear->id,
            'teacher_id' => $teacher->id,
            'capacity' => 30,
        ]);
        $nonAdviserClass = Classes::create([
            'section_id' => $nonAdviserSection->id,
            'school_year_id' => $schoolYear->id,
            'teacher_id' => $otherAdviser->id,
            'capacity' => 30,
        ]);

        $subject = $this->createSubject($gradeLevel, $suffix);
        Schedule::create([
            'class_id' => $adviserClass->id,
            'subject_id' => $subject->id,
            'teacher_id' => $teacher->id,
            'day_of_week' => ['monday'],
            'start_time' => '08:00',
            'end_time' => '09:00',
            'room' => 'R1',
        ]);
        Schedule::create([
            'class_id' => $nonAdviserClass->id,
            'subject_id' => $subject->id,
            'teacher_id' => $teacher->id,
            'day_of_week' => ['tuesday'],
            'start_time' => '09:00',
            'end_time' => '10:00',
            'room' => 'R2',
        ]);

        $response = $this->actingAs($teacherUser)->get(route('teacher.attendance.take'));

        $response->assertOk();
        $response->assertSee('id="allDayToggleWrapper"', false);
        $response->assertSee('id="allDayGradeWarning"', false);
        $response->assertSee('data-is-adviser="1"', false);
        $response->assertSee('data-is-adviser="0"', false);
    }

    public function test_attendance_page_keeps_all_subject_toggle_hidden_for_teacher_with_no_advisory_classes(): void
    {
        $suffix = Str::lower(Str::random(8));
        $schoolYear = $this->createActiveSchoolYear($suffix);
        $gradeLevel = $this->createGradeLevel($suffix, 5);
        $section = $this->createSection('ONLYNONADV', $suffix, $gradeLevel);

        [$teacherUser, $teacher] = $this->createTeacherUser();
        [, $adviser] = $this->createTeacherUser();

        $class = Classes::create([
            'section_id' => $section->id,
            'school_year_id' => $schoolYear->id,
            'teacher_id' => $adviser->id,
            'capacity' => 30,
        ]);

        $subject = $this->createSubject($gradeLevel, $suffix);
        Schedule::create([
            'class_id' => $class->id,
            'subject_id' => $subject->id,
            'teacher_id' => $teacher->id,
            'day_of_week' => ['monday'],
            'start_time' => '08:00',
            'end_time' => '09:00',
            'room' => 'R1',
        ]);

        $response = $this->actingAs($teacherUser)->get(route('teacher.attendance.take'));

        $response->assertOk();
        $response->assertDontSee('data-is-adviser="1"', false);
        $response->assertSee('id="allDayToggleWrapper"', false);
        $response->assertSee('class="mb-3 d-none" id="allDayToggleWrapper"', false);
    }

    public function test_attendance_page_shows_warning_for_teacher_with_no_handled_subjects(): void
    {
        $suffix = Str::lower(Str::random(8));
        $this->createActiveSchoolYear($suffix);
        [$teacherUser] = $this->createTeacherUser();

        $response = $this->actingAs($teacherUser)->get(route('teacher.attendance.take'));

        $response->assertOk();
        $response->assertSee('id="noHandledSubjectWarning"', false);
        $response->assertSee('You do not have any handled subjects yet.', false);
    }

    public function test_attendance_all_subject_for_grade_4_to_6_adviser_returns_warning_and_uses_resolved_class_id(): void
    {
        $suffix = Str::lower(Str::random(8));
        $schoolYear = $this->createActiveSchoolYear($suffix);
        $gradeLevel = $this->createGradeLevel($suffix, 4);

        // Create an extra section first so section IDs and class IDs are not accidentally identical.
        $this->createSection('EXTRA', $suffix, $gradeLevel);
        $section = $this->createSection('ADV', $suffix, $gradeLevel);

        [$adviserUser, $adviser] = $this->createTeacherUser();
        [, $subjectTeacher] = $this->createTeacherUser();

        $class = Classes::create([
            'section_id' => $section->id,
            'school_year_id' => $schoolYear->id,
            'teacher_id' => $adviser->id,
            'capacity' => 30,
        ]);

        $subjectA = $this->createSubject($gradeLevel, $suffix.'a');
        $subjectB = $this->createSubject($gradeLevel, $suffix.'b');

        Schedule::create([
            'class_id' => $class->id,
            'subject_id' => $subjectA->id,
            'teacher_id' => $subjectTeacher->id,
            'day_of_week' => ['monday'],
            'start_time' => '08:00',
            'end_time' => '09:00',
            'room' => 'R1',
        ]);
        Schedule::create([
            'class_id' => $class->id,
            'subject_id' => $subjectB->id,
            'teacher_id' => $subjectTeacher->id,
            'day_of_week' => ['tuesday'],
            'start_time' => '09:00',
            'end_time' => '10:00',
            'room' => 'R2',
        ]);

        $student = $this->createStudent($suffix);
        $this->enrollStudent($student, $class, $schoolYear, $adviser);

        $getResponse = $this->actingAs($adviserUser)->getJson(route('teacher.attendance.get-students', [
            'section_id' => $section->id,
            'subject_id' => 'all',
            'date' => now()->toDateString(),
        ]));
        $getResponse->assertOk();
        $getResponse->assertJsonPath(
            'warning',
            'All-subject attendance marks all scheduled class subjects, including those not handled by you.'
        );

        $saveResponse = $this->actingAs($adviserUser)->postJson(route('teacher.attendance.save'), [
            'section_id' => $section->id,
            'subject_id' => 'all',
            'date' => now()->toDateString(),
            'quarter' => '1st Quarter',
            'status' => [
                $student->id => 'present',
            ],
            'remarks' => [],
        ]);

        $saveResponse->assertOk();
        $saveResponse->assertJsonPath('success', true);
        $saveResponse->assertJsonPath(
            'warning',
            'All-subject attendance marks all scheduled class subjects, including those not handled by you.'
        );

        $attendanceRows = Attendance::query()
            ->where('student_id', $student->id)
            ->where('school_year_id', $schoolYear->id)
            ->get();

        $this->assertNotEmpty($attendanceRows);
        $this->assertCount(2, $attendanceRows);
        $this->assertTrue($attendanceRows->every(fn ($row) => (int) $row->class_id === (int) $class->id));
        $this->assertFalse($attendanceRows->contains(fn ($row) => (int) $row->class_id === (int) $section->id));
    }

    private function createActiveSchoolYear(string $suffix): SchoolYear
    {
        $schoolYear = SchoolYear::create([
            'name' => 'SY-'.$suffix,
            'start_date' => now()->startOfMonth()->toDateString(),
            'end_date' => now()->addMonths(10)->endOfMonth()->toDateString(),
            'is_active' => true,
            'is_promotion_open' => false,
        ]);

        SchoolYearQuarter::create([
            'school_year_id' => $schoolYear->id,
            'quarter' => 1,
            'name' => 'First Quarter',
            'start_date' => now()->subMonth()->toDateString(),
            'end_date' => now()->addMonth()->toDateString(),
            'grade_submission_deadline' => now()->addMonth()->toDateString(),
            'is_locked' => false,
            'is_manually_set_active' => true,
        ]);

        return $schoolYear;
    }

    private function createGradeLevel(string $suffix, int $level): GradeLevel
    {
        return GradeLevel::create([
            'name' => 'Grade '.$level.' '.$suffix,
            'level' => $level,
            'description' => 'Grade level '.$suffix,
        ]);
    }

    private function createSection(string $prefix, string $suffix, GradeLevel $gradeLevel): Section
    {
        return Section::create([
            'name' => $prefix.'-'.$suffix,
            'grade_level_id' => $gradeLevel->id,
            'description' => $prefix.' section',
        ]);
    }

    private function createSubject(GradeLevel $gradeLevel, string $suffix): Subject
    {
        return Subject::create([
            'grade_level_id' => $gradeLevel->id,
            'name' => 'Subject-'.$suffix,
            'code' => 'SUB-'.Str::upper(Str::random(5)),
            'description' => 'Test subject',
            'is_active' => true,
        ]);
    }

    private function createStudent(string $suffix): Student
    {
        return Student::create([
            'student_id' => 'STD-'.Str::upper(Str::random(6)),
            'lrn' => fake()->unique()->numerify('###########'),
            'first_name' => 'Student',
            'last_name' => strtoupper(substr($suffix, 0, 6)),
            'gender' => 'male',
            'birthdate' => '2014-01-01',
            'enrollment_date' => now()->toDateString(),
        ]);
    }

    private function enrollStudent(Student $student, Classes $class, SchoolYear $schoolYear, Teacher $teacher): void
    {
        Enrollment::create([
            'student_id' => $student->id,
            'class_id' => $class->id,
            'school_year_id' => $schoolYear->id,
            'teacher_id' => $teacher->id,
            'enrollment_date' => now()->toDateString(),
            'status' => 'enrolled',
        ]);
    }

    private function ensureTeacherRole(): Role
    {
        return Role::firstOrCreate(
            ['name' => 'teacher'],
            ['description' => 'Teacher']
        );
    }

    private function createTeacherUser(): array
    {
        $role = $this->ensureTeacherRole();
        $user = User::factory()->create([
            'role_id' => $role->id,
        ]);

        $teacher = Teacher::create([
            'user_id' => $user->id,
            'phone' => '09'.fake()->numerify('#########'),
            'gender' => fake()->randomElement(['male', 'female']),
            'date_of_birth' => '1990-01-01',
            'address' => 'Teacher address',
            'qualification' => 'Bachelor of Education',
            'status' => 'active',
        ]);

        return [$user, $teacher];
    }
}
