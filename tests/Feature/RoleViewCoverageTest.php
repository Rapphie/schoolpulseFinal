<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\AssessmentScore;
use App\Models\Attendance;
use App\Models\Classes;
use App\Models\Enrollment;
use App\Models\GradeLevel;
use App\Models\Guardian;
use App\Models\LLC;
use App\Models\LLCItem;
use App\Models\Role;
use App\Models\Schedule;
use App\Models\SchoolYear;
use App\Models\SchoolYearQuarter;
use App\Models\Section;
use App\Models\Setting;
use App\Models\Student;
use App\Models\StudentProfile;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Tests\TestCase;

class RoleViewCoverageTest extends TestCase
{
    use DatabaseTransactions;

    private User $adminUser;

    private User $teacherUser;

    private User $guardianUser;

    private Teacher $teacher;

    private SchoolYear $schoolYear;

    private GradeLevel $gradeLevel;

    private Section $section;

    private Classes $class;

    private Subject $subject;

    private Student $student;

    private StudentProfile $studentProfile;

    private Enrollment $enrollment;

    private Schedule $schedule;

    private Assessment $assessment;

    private LLC $llc;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seedEnvironment();
    }

    public function test_admin_views_render_successfully(): void
    {
        $routes = [
            ['admin.dashboard'],
            ['admin.grade-levels.index'],
            ['admin.sections.index'],
            ['admin.sections.create'],
            ['admin.sections.show', ['section' => $this->section]],
            ['admin.sections.edit', ['section' => $this->section]],
            ['admin.sections.manage', ['section' => $this->section]],
            ['admin.students.show', ['student' => $this->student]],
            ['admin.students.edit', ['student' => $this->student]],
            ['admin.subjects.index'],
            ['admin.analytics.show'],
            ['admin.teachers.index'],
            ['admin.teachers.create'],
            ['admin.teachers.show', ['teacher' => $this->teacherUser]],
            ['admin.teachers.edit', ['teacher' => $this->teacherUser]],
            ['admin.schedules.index'],
            ['admin.schedules.create'],
            ['admin.schedules.show', ['schedule' => $this->schedule]],
            ['admin.schedules.edit', ['schedule' => $this->schedule]],
            ['admin.settings.index'],
            ['admin.reports.enrollees'],
            ['admin.reports.enrollees.detail', ['type' => 'students']],
            ['admin.reports.attendance'],
            ['admin.reports.attendance.detail', ['type' => 'records']],
            ['admin.reports.grades'],
            ['admin.reports.grades.detail', ['type' => 'records']],
            ['admin.reports.cumulative'],
            ['admin.reports.least-learned'],
            ['admin.school-year.quarters.index', ['schoolYear' => $this->schoolYear]],
        ];

        foreach ($routes as $route) {
            $name = $route[0];
            $parameters = $route[1] ?? [];

            $response = $this->actingAs($this->adminUser)->get(route($name, $parameters));
            $response->assertStatus(200);
        }
    }

    public function test_teacher_views_render_successfully(): void
    {
        $routes = [
            ['teacher.dashboard'],
            ['teacher.classes'],
            ['teacher.classes.view', ['class' => $this->class]],
            ['teacher.schedules.index'],
            ['teacher.students.index'],
            ['teacher.students.create'],
            ['teacher.students.show', ['student' => $this->student]],
            ['teacher.students.edit', ['student' => $this->student]],
            ['teacher.students.grades', ['student' => $this->student, 'sy' => $this->schoolYear]],
            ['teacher.enrollment.index'],
            ['teacher.assessments.list'],
            ['teacher.assessments.index', ['class' => $this->class]],
            ['teacher.assessments.create', ['class' => $this->class]],
            ['teacher.assessments.scores.edit', ['class' => $this->class, 'assessment' => $this->assessment]],
            ['teacher.grades'],
            ['teacher.grades.show', ['class' => $this->class]],
            ['teacher.grades.student', ['class' => $this->class, 'student' => $this->student]],
            ['teacher.attendance.take'],
            ['teacher.attendance.records'],
            ['teacher.attendance.pattern'],
            ['teacher.least-learned.index'],
            ['teacher.least-learned.show', ['llc' => $this->llc]],
            ['teacher.oral-participation.index', ['class' => $this->class]],
            ['teacher.analytics.absenteeism'],
            ['teacher.report-cards'],
        ];

        foreach ($routes as $route) {
            $name = $route[0];
            $parameters = $route[1] ?? [];

            $response = $this->actingAs($this->teacherUser)->get(route($name, $parameters));
            $response->assertStatus(200);
        }
    }

    public function test_admin_schedule_page_uses_bootstrap_layout_markup(): void
    {
        $response = $this->actingAs($this->adminUser)->get(route('admin.schedules.index'));

        $response->assertStatus(200);
        $response->assertSee('d-flex justify-content-between align-items-center mb-4', false);
        $response->assertSee('btn btn-primary d-inline-flex align-items-center', false);
        $response->assertSee('bg-white p-4 rounded shadow-sm', false);
        $response->assertDontSee('inline-flex items-center bg-blue-500 hover:bg-blue-700', false);
        $response->assertDontSee('bg-white p-6 rounded-lg shadow-md', false);
        $response->assertDontSee('font-semibold truncate', false);
    }

    public function test_teacher_schedule_page_uses_bootstrap_layout_markup(): void
    {
        $response = $this->actingAs($this->teacherUser)->get(route('teacher.schedules.index'));

        $response->assertStatus(200);
        $response->assertSee('d-flex justify-content-between align-items-center mb-4', false);
        $response->assertSee('bg-white p-4 rounded shadow-sm', false);
        $response->assertDontSee('bg-white p-6 rounded-lg shadow-md', false);
        $response->assertDontSee('font-semibold truncate', false);
    }

    public function test_teacher_students_overview_redirects_to_student_profiles_page(): void
    {
        $response = $this->actingAs($this->teacherUser)->get(route('teacher.students-overview'));

        $response->assertRedirect(route('teacher.students.index'));
    }

    public function test_guardian_dashboard_renders_successfully(): void
    {
        $response = $this->actingAs($this->guardianUser)->get(route('guardian.dashboard'));

        $response->assertStatus(200);
    }

    public function test_teacher_dashboard_accepts_subject_filter(): void
    {
        $response = $this->actingAs($this->teacherUser)->get(
            route('teacher.dashboard', ['subject_id' => $this->subject->id])
        );

        $response->assertStatus(200);
    }

    public function test_teacher_assessment_scores_update_accepts_put(): void
    {
        $response = $this->actingAs($this->teacherUser)->put(
            route('teacher.assessments.scores.update', ['class' => $this->class, 'assessment' => $this->assessment]),
            [
                'scores' => [
                    $this->student->id => [
                        'student_id' => $this->student->id,
                        'score' => 8,
                        'remarks' => 'Updated by test',
                    ],
                ],
            ]
        );

        $response->assertRedirect(route('teacher.assessments.index', ['class' => $this->class]));

        $this->assertDatabaseHas('assessment_scores', [
            'assessment_id' => $this->assessment->id,
            'student_id' => $this->student->id,
            'remarks' => 'Updated by test',
        ]);
    }

    private function seedEnvironment(): void
    {
        $suffix = Str::lower(Str::random(8));

        $this->ensureRole('admin', 1);
        $this->ensureRole('teacher', 2);
        $this->ensureRole('guardian', 3);

        $this->schoolYear = SchoolYear::create([
            'name' => '2099-2100-'.$suffix,
            'start_date' => now()->startOfYear()->toDateString(),
            'end_date' => now()->endOfYear()->toDateString(),
            'is_active' => true,
            'is_promotion_open' => true,
        ]);

        $this->gradeLevel = GradeLevel::create([
            'name' => 'Grade 4 '.$suffix,
            'level' => random_int(10, 99),
            'description' => 'Role view coverage test',
        ]);

        $this->section = Section::create([
            'name' => 'S-'.$suffix,
            'grade_level_id' => $this->gradeLevel->id,
            'description' => 'Role view coverage section',
        ]);

        $this->adminUser = User::factory()->create([
            'role_id' => 1,
            'temporary_password' => null,
        ]);

        $this->teacherUser = User::factory()->create([
            'role_id' => 2,
            'temporary_password' => null,
        ]);

        $this->guardianUser = User::factory()->create([
            'role_id' => 3,
            'temporary_password' => null,
        ]);

        $this->teacher = Teacher::create([
            'user_id' => $this->teacherUser->id,
            'phone' => '09'.fake()->numerify('#########'),
            'gender' => 'male',
            'date_of_birth' => '1990-01-01',
            'address' => 'Teacher address',
            'qualification' => 'Bachelor of Education',
            'status' => 'active',
        ]);

        $guardian = Guardian::create([
            'user_id' => $this->guardianUser->id,
            'phone' => '09'.fake()->numerify('#########'),
            'relationship' => 'parent',
        ]);

        $this->class = Classes::create([
            'section_id' => $this->section->id,
            'school_year_id' => $this->schoolYear->id,
            'teacher_id' => $this->teacher->id,
            'capacity' => 40,
        ]);

        $this->subject = Subject::create([
            'grade_level_id' => $this->gradeLevel->id,
            'name' => 'Subject '.$suffix,
            'code' => strtoupper('S'.$suffix),
            'description' => 'Subject description',
            'is_active' => true,
            'duration_minutes' => 60,
        ]);

        $this->student = Student::create([
            'student_id' => 'STD-'.$suffix,
            'lrn' => fake()->unique()->numerify('############'),
            'first_name' => 'Student',
            'last_name' => 'Coverage',
            'gender' => 'male',
            'birthdate' => '2013-01-01',
            'guardian_id' => $guardian->id,
            'enrollment_date' => now()->toDateString(),
            'address' => 'Student address',
        ]);

        $this->studentProfile = StudentProfile::create([
            'student_id' => $this->student->id,
            'school_year_id' => $this->schoolYear->id,
            'grade_level_id' => $this->gradeLevel->id,
            'status' => 'enrolled',
            'created_by_teacher_id' => $this->teacher->id,
        ]);

        $this->enrollment = Enrollment::create([
            'student_id' => $this->student->id,
            'class_id' => $this->class->id,
            'school_year_id' => $this->schoolYear->id,
            'teacher_id' => $this->teacher->id,
            'enrolled_by_user_id' => $this->adminUser->id,
            'student_profile_id' => $this->studentProfile->id,
            'enrollment_date' => now()->toDateString(),
            'status' => 'enrolled',
        ]);

        $this->schedule = Schedule::create([
            'class_id' => $this->class->id,
            'subject_id' => $this->subject->id,
            'teacher_id' => $this->teacher->id,
            'day_of_week' => ['monday'],
            'start_time' => '08:00:00',
            'end_time' => '09:00:00',
            'room' => 'R1',
        ]);

        SchoolYearQuarter::create([
            'school_year_id' => $this->schoolYear->id,
            'quarter' => 1,
            'name' => 'First Quarter',
            'start_date' => now()->startOfMonth()->toDateString(),
            'end_date' => now()->endOfMonth()->toDateString(),
            'grade_submission_deadline' => now()->endOfMonth()->toDateString(),
            'is_locked' => false,
            'is_manually_set_active' => true,
        ]);

        $this->assessment = Assessment::create([
            'class_id' => $this->class->id,
            'subject_id' => $this->subject->id,
            'teacher_id' => $this->teacher->id,
            'school_year_id' => $this->schoolYear->id,
            'name' => 'Quiz 1',
            'type' => 'written_works',
            'max_score' => 10,
            'quarter' => 1,
            'assessment_date' => now()->toDateString(),
        ]);

        AssessmentScore::create([
            'assessment_id' => $this->assessment->id,
            'student_id' => $this->student->id,
            'student_profile_id' => $this->studentProfile->id,
            'score' => 7,
            'remarks' => 'Initial score',
        ]);

        Attendance::create([
            'student_id' => $this->student->id,
            'class_id' => $this->class->id,
            'subject_id' => $this->subject->id,
            'teacher_id' => $this->teacher->id,
            'school_year_id' => $this->schoolYear->id,
            'student_profile_id' => $this->studentProfile->id,
            'status' => 'present',
            'date' => now()->toDateString(),
            'quarter' => '1',
            'quarter_int' => 1,
            'time_in' => '08:01:00',
        ]);

        $this->llc = LLC::create([
            'subject_id' => $this->subject->id,
            'section_id' => $this->section->id,
            'teacher_id' => $this->teacher->id,
            'school_year_id' => $this->schoolYear->id,
            'quarter' => 1,
            'exam_title' => 'Summative Test',
            'total_students' => 1,
            'total_items' => 10,
        ]);

        LLCItem::create([
            'llc_id' => $this->llc->id,
            'item_number' => 1,
            'students_wrong' => 1,
            'category_name' => 'Numeracy',
            'item_start' => 1,
            'item_end' => 1,
        ]);

        Setting::updateOrCreate(['key' => 'teacher_enrollment'], ['value' => '1']);
        Setting::updateOrCreate(['key' => 'school_year'], ['value' => $this->schoolYear->name]);

        Cache::forget('sidebar_settings');
    }

    private function ensureRole(string $name, int $id): void
    {
        Role::firstOrCreate(
            ['id' => $id],
            ['name' => $name, 'description' => ucfirst($name)]
        );
    }
}
