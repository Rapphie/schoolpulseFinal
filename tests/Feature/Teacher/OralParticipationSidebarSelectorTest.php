<?php

namespace Tests\Feature\Teacher;

use App\Models\Classes;
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

class OralParticipationSidebarSelectorTest extends TestCase
{
    use DatabaseTransactions;

    private SchoolYear $activeSchoolYear;

    private User $teacherUser;

    private Teacher $teacher;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class);
        $this->withoutVite();
        $this->ensureRoles();

        SchoolYearQuarter::query()->update(['is_manually_set_active' => false]);
        SchoolYear::query()->update(['is_active' => false]);

        $this->activeSchoolYear = SchoolYear::create([
            'name' => '2099-2100-selector-'.Str::lower(Str::random(6)),
            'start_date' => now()->startOfYear()->toDateString(),
            'end_date' => now()->endOfYear()->toDateString(),
            'is_active' => true,
            'is_promotion_open' => true,
        ]);

        ['user' => $this->teacherUser, 'teacher' => $this->teacher] = $this->createTeacherPair('primary');
    }

    public function test_selector_returns_elementary_adviser_subjects_with_class_and_subject_ids(): void
    {
        $gradeOne = $this->createGradeLevel(1);
        $advisorySection = $this->createSection($gradeOne, 'Elem-Section');
        $advisoryClass = $this->createClass($advisorySection, $this->teacher);

        $math = $this->createSubject($gradeOne, 'Mathematics');
        $science = $this->createSubject($gradeOne, 'Science');

        $this->createSchedule($advisoryClass, $math, $this->teacher);
        $this->createSchedule($advisoryClass, $science, $this->teacher);

        $response = $this->actingAs($this->teacherUser)
            ->getJson(route('teacher.oral-participation.selector'));

        $response->assertOk();
        $response->assertJsonPath('mode', 'elementary_adviser');
        $response->assertJsonFragment([
            'class_id' => $advisoryClass->id,
            'subject_id' => $math->id,
            'subject_name' => $math->name,
        ]);
        $response->assertJsonFragment([
            'class_id' => $advisoryClass->id,
            'subject_id' => $science->id,
            'subject_name' => $science->name,
        ]);

        $subjects = collect($response->json('subjects'));
        $this->assertCount(2, $subjects);
        $this->assertTrue($subjects->every(function (array $subject): bool {
            return isset($subject['class_id'], $subject['subject_id'], $subject['subject_name']);
        }));
    }

    public function test_selector_returns_departmental_grade_levels_from_logged_teacher_schedules_only(): void
    {
        $gradeTwo = $this->createGradeLevel(2);
        $gradeFour = $this->createGradeLevel(4);
        $gradeSix = $this->createGradeLevel(6);

        $classGradeFour = $this->createClass($this->createSection($gradeFour, 'Dept-G4'));
        $classGradeSix = $this->createClass($this->createSection($gradeSix, 'Dept-G6'));
        $classGradeTwo = $this->createClass($this->createSection($gradeTwo, 'Dept-G2'));

        $subjectGradeFour = $this->createSubject($gradeFour, 'English');
        $subjectGradeSix = $this->createSubject($gradeSix, 'Araling Panlipunan');
        $subjectGradeTwo = $this->createSubject($gradeTwo, 'Mother Tongue');

        ['teacher' => $otherTeacher] = $this->createTeacherPair('other');

        $this->createSchedule($classGradeFour, $subjectGradeFour, $this->teacher);
        $this->createSchedule($classGradeSix, $subjectGradeSix, $this->teacher);
        $this->createSchedule($classGradeTwo, $subjectGradeTwo, $otherTeacher);

        $response = $this->actingAs($this->teacherUser)
            ->getJson(route('teacher.oral-participation.selector'));

        $response->assertOk();
        $response->assertJsonPath('mode', 'departmental');

        $gradeLevelIds = collect($response->json('grade_levels'))->pluck('id')->all();
        $this->assertContains($gradeFour->id, $gradeLevelIds);
        $this->assertContains($gradeSix->id, $gradeLevelIds);
        $this->assertNotContains($gradeTwo->id, $gradeLevelIds);
    }

    public function test_sections_endpoint_returns_only_logged_teacher_scheduled_classes_for_selected_grade_level(): void
    {
        $gradeFour = $this->createGradeLevel(4);
        $gradeFive = $this->createGradeLevel(5);

        $includedSection = $this->createSection($gradeFour, 'Included');
        $otherTeacherSection = $this->createSection($gradeFour, 'OtherTeacher');
        $noScheduleSection = $this->createSection($gradeFour, 'NoSchedule');
        $differentGradeSection = $this->createSection($gradeFive, 'DifferentGrade');

        $includedClass = $this->createClass($includedSection);
        $otherTeacherClass = $this->createClass($otherTeacherSection);
        $this->createClass($noScheduleSection);
        $differentGradeClass = $this->createClass($differentGradeSection);

        $gradeFourSubjectA = $this->createSubject($gradeFour, 'Filipino');
        $gradeFourSubjectB = $this->createSubject($gradeFour, 'Arts');
        $gradeFiveSubject = $this->createSubject($gradeFive, 'Mathematics');

        ['teacher' => $otherTeacher] = $this->createTeacherPair('sections-other');

        $this->createSchedule($includedClass, $gradeFourSubjectA, $this->teacher);
        $this->createSchedule($otherTeacherClass, $gradeFourSubjectB, $otherTeacher);
        $this->createSchedule($differentGradeClass, $gradeFiveSubject, $this->teacher);

        $response = $this->actingAs($this->teacherUser)
            ->getJson(route('teacher.oral-participation.sections', ['grade_level_id' => $gradeFour->id]));

        $response->assertOk();

        $sections = collect($response->json('sections'));
        $this->assertCount(1, $sections);
        $this->assertSame($includedClass->id, $sections->first()['id']);
        $this->assertSame($includedClass->id, $sections->first()['class_id']);
        $this->assertSame($includedSection->name, $sections->first()['name']);
    }

    public function test_oral_participation_root_url_returns_not_found_after_list_route_removal(): void
    {
        $response = $this->actingAs($this->teacherUser)->get('/teacher/oral-participation');

        $response->assertNotFound();
    }

    public function test_recitation_mode_quarter_selector_is_locked_to_active_school_year_quarter(): void
    {
        $gradeFour = $this->createGradeLevel(4);
        $section = $this->createSection($gradeFour, 'Q-Lock');
        $class = $this->createClass($section);
        $subject = $this->createSubject($gradeFour, 'Science');
        $this->createSchedule($class, $subject, $this->teacher);

        SchoolYearQuarter::create([
            'school_year_id' => $this->activeSchoolYear->id,
            'quarter' => 3,
            'name' => 'Third Quarter',
            'start_date' => now()->subDays(2)->toDateString(),
            'end_date' => now()->addDays(2)->toDateString(),
            'is_locked' => false,
            'is_manually_set_active' => true,
        ]);

        $response = $this->actingAs($this->teacherUser)
            ->get(route('teacher.oral-participation.index', ['class' => $class->id, 'subject_id' => $subject->id]));

        $response->assertOk();

        $content = $response->getContent();
        $this->assertMatchesRegularExpression('/<select[^>]*id="recitationQuarter"[^>]*disabled/s', $content);
        $this->assertMatchesRegularExpression('/<option value="3"[^>]*selected[^>]*>\\s*Quarter 3\\s*<\\/option>/s', $content);
    }

    public function test_append_scores_rejects_non_active_quarter_submission(): void
    {
        $gradeFour = $this->createGradeLevel(4);
        $section = $this->createSection($gradeFour, 'Q-Guard');
        $class = $this->createClass($section);
        $subject = $this->createSubject($gradeFour, 'Mathematics');
        $this->createSchedule($class, $subject, $this->teacher);

        $student = Student::create([
            'student_id' => 'SID-'.Str::upper(Str::random(8)),
            'first_name' => 'Quarter',
            'last_name' => 'Mismatch',
            'gender' => 'male',
            'birthdate' => '2014-01-01',
            'enrollment_date' => now()->toDateString(),
        ]);

        SchoolYearQuarter::create([
            'school_year_id' => $this->activeSchoolYear->id,
            'quarter' => 2,
            'name' => 'Second Quarter',
            'start_date' => now()->subDays(2)->toDateString(),
            'end_date' => now()->addDays(2)->toDateString(),
            'is_locked' => false,
            'is_manually_set_active' => true,
        ]);

        $response = $this->actingAs($this->teacherUser)
            ->postJson(route('teacher.oral-participation.appendScores', ['class' => $class->id]), [
                'subject_id' => $subject->id,
                'quarter' => 1,
                'session_max_score' => 10,
                'scores' => [
                    [
                        'student_id' => $student->id,
                        'score' => 8,
                    ],
                ],
            ]);

        $response->assertStatus(422);
        $response->assertJsonPath('success', false);
        $response->assertJsonPath(
            'message',
            'Oral participation sessions can only be recorded for the active quarter (Quarter 2).'
        );
    }

    private function ensureRoles(): void
    {
        Role::firstOrCreate(['id' => 1], ['name' => 'admin', 'description' => 'Administrator']);
        Role::firstOrCreate(['id' => 2], ['name' => 'teacher', 'description' => 'Teacher']);
        Role::firstOrCreate(['id' => 3], ['name' => 'guardian', 'description' => 'Guardian']);
    }

    /**
     * @return array{user: User, teacher: Teacher}
     */
    private function createTeacherPair(string $label): array
    {
        $suffix = Str::lower(Str::random(6));

        $user = User::factory()->create([
            'role_id' => 2,
            'temporary_password' => null,
            'email' => "{$label}-{$suffix}@example.test",
        ]);

        $teacher = Teacher::create([
            'user_id' => $user->id,
            'phone' => '09'.fake()->numerify('#########'),
            'gender' => 'female',
            'date_of_birth' => '1990-01-01',
            'address' => 'Selector Test Address',
            'qualification' => 'Bachelor of Education',
            'status' => 'active',
        ]);

        return [
            'user' => $user,
            'teacher' => $teacher,
        ];
    }

    private function createGradeLevel(int $level): GradeLevel
    {
        return GradeLevel::firstOrCreate(
            ['level' => $level],
            [
                'name' => 'Grade '.$level,
                'description' => "Grade {$level} test data",
            ]
        );
    }

    private function createSection(GradeLevel $gradeLevel, string $prefix): Section
    {
        return Section::create([
            'name' => "{$prefix}-".Str::upper(Str::random(5)),
            'grade_level_id' => $gradeLevel->id,
            'description' => 'Selector section',
        ]);
    }

    private function createClass(Section $section, ?Teacher $adviser = null): Classes
    {
        return Classes::create([
            'section_id' => $section->id,
            'school_year_id' => $this->activeSchoolYear->id,
            'teacher_id' => $adviser?->id,
            'capacity' => 40,
        ]);
    }

    private function createSubject(GradeLevel $gradeLevel, string $prefix): Subject
    {
        $suffix = Str::lower(Str::random(6));

        return Subject::create([
            'grade_level_id' => $gradeLevel->id,
            'name' => "{$prefix} {$suffix}",
            'code' => Str::upper(Str::random(2)).random_int(100, 999).Str::upper(Str::random(1)),
            'description' => 'Selector subject',
            'duration_minutes' => 60,
            'is_active' => true,
        ]);
    }

    private function createSchedule(Classes $class, Subject $subject, Teacher $teacher): Schedule
    {
        return Schedule::create([
            'class_id' => $class->id,
            'subject_id' => $subject->id,
            'teacher_id' => $teacher->id,
            'day_of_week' => ['monday'],
            'start_time' => '08:00:00',
            'end_time' => '09:00:00',
            'room' => 'R-101',
        ]);
    }
}
