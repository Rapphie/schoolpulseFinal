<?php

namespace Tests\Feature\Teacher;

use App\Models\Classes;
use App\Models\Enrollment;
use App\Models\Grade;
use App\Models\GradeLevel;
use App\Models\Role;
use App\Models\SchoolYear;
use App\Models\SchoolYearQuarter;
use App\Models\Section;
use App\Models\Student;
use App\Models\StudentProfile;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ReportCardGradeVisibilityTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    private function ensureRole(string $name, int $id): Role
    {
        return Role::firstOrCreate(
            ['id' => $id],
            ['name' => $name, 'description' => ucfirst($name)]
        );
    }

    /**
     * @return array{user: User, teacher: Teacher, schoolYear: SchoolYear, gradeLevel: GradeLevel, section: Section, class: Classes, student: Student, profile: StudentProfile}
     */
    private function buildTeacherClassEnvironment(int $gradeLevelValue = 4): array
    {
        $this->ensureRole('teacher', 2);

        $schoolYear = SchoolYear::firstOrCreate(
            ['name' => '2025-2026-test-report-card-rules'],
            ['start_date' => '2025-06-01', 'end_date' => '2026-03-31', 'is_active' => true]
        );

        SchoolYearQuarter::query()->update(['is_manually_set_active' => false]);
        $schoolYear->update(['is_active' => true]);

        $gradeLevel = GradeLevel::firstOrCreate(
            ['level' => $gradeLevelValue],
            ['name' => 'Grade '.$gradeLevelValue, 'description' => 'Report Card Rules Test Grade']
        );

        $section = Section::firstOrCreate(
            ['name' => 'ReportCardRules-Sec-'.$gradeLevelValue, 'grade_level_id' => $gradeLevel->id],
            ['description' => 'Report card rule test section']
        );

        $user = User::factory()->create([
            'role_id' => 2,
            'temporary_password' => null,
        ]);

        $teacher = Teacher::create([
            'user_id' => $user->id,
            'phone' => '09'.fake()->numerify('#########'),
            'gender' => 'male',
            'date_of_birth' => '1990-01-01',
            'address' => 'Test Address',
            'qualification' => 'Bachelor of Education',
            'status' => 'active',
        ]);

        $class = Classes::firstOrCreate(
            ['section_id' => $section->id, 'school_year_id' => $schoolYear->id],
            ['teacher_id' => $teacher->id, 'capacity' => 40]
        );
        $class->update(['teacher_id' => $teacher->id]);

        $student = Student::create([
            'student_id' => 'RCR-'.now()->timestamp.'-'.fake()->numerify('###'),
            'lrn' => fake()->unique()->numerify('############'),
            'first_name' => 'Report',
            'last_name' => 'CardStudent'.fake()->numerify('###'),
            'gender' => 'male',
            'birthdate' => '2015-01-01',
        ]);

        $profile = StudentProfile::create([
            'student_id' => $student->id,
            'school_year_id' => $schoolYear->id,
            'grade_level_id' => $gradeLevel->id,
            'status' => 'enrolled',
        ]);

        Enrollment::create([
            'student_id' => $student->id,
            'class_id' => $class->id,
            'school_year_id' => $schoolYear->id,
            'student_profile_id' => $profile->id,
            'teacher_id' => $teacher->id,
            'enrollment_date' => now(),
            'status' => 'enrolled',
        ]);

        return compact('user', 'teacher', 'schoolYear', 'gradeLevel', 'section', 'class', 'student', 'profile');
    }

    private function createSubject(int $gradeLevelId, string $name): Subject
    {
        return Subject::create([
            'grade_level_id' => $gradeLevelId,
            'name' => $name.' '.strtoupper(fake()->unique()->lexify('??')),
            'code' => strtoupper(fake()->unique()->bothify('RC###??')),
            'description' => 'Test subject',
            'is_active' => true,
        ]);
    }

    private function addGradeEntries(array $context, Subject $subject, array $quarterGrades): void
    {
        foreach ($quarterGrades as $quarter => $gradeValue) {
            Grade::create([
                'student_id' => $context['student']->id,
                'student_profile_id' => $context['profile']->id,
                'subject_id' => $subject->id,
                'teacher_id' => $context['teacher']->id,
                'school_year_id' => $context['schoolYear']->id,
                'grade' => $gradeValue,
                'quarter' => (string) $quarter,
            ]);
        }
    }

    public function test_preview_hides_general_average_when_a_required_grade_level_subject_is_missing(): void
    {
        $context = $this->buildTeacherClassEnvironment();
        $math = $this->createSubject($context['gradeLevel']->id, 'Mathematics 4');
        $this->createSubject($context['gradeLevel']->id, 'Science 4');

        $this->addGradeEntries($context, $math, [
            1 => 85.0,
            2 => 86.0,
            3 => 87.0,
            4 => 88.0,
        ]);

        $response = $this->actingAs($context['user'])
            ->get(route('teacher.grades.student', [$context['class'], $context['student']]));

        $response->assertStatus(200);
        $response->assertViewHas('generalAverage', null);
    }

    public function test_preview_sets_subject_final_grade_and_remarks_as_blank_when_any_quarter_is_missing(): void
    {
        $context = $this->buildTeacherClassEnvironment();
        $math = $this->createSubject($context['gradeLevel']->id, 'Mathematics 4');
        $science = $this->createSubject($context['gradeLevel']->id, 'Science 4');

        $this->addGradeEntries($context, $math, [
            1 => 90.0,
            2 => 90.0,
            3 => 90.0,
            4 => 90.0,
        ]);
        $this->addGradeEntries($context, $science, [
            1 => 85.0,
            2 => 86.0,
            3 => 87.0,
        ]);

        $response = $this->actingAs($context['user'])
            ->get(route('teacher.grades.student', [$context['class'], $context['student']]));

        $response->assertStatus(200);
        $response->assertViewHas('generalAverage', null);
        $response->assertViewHas('gradesData', function (array $gradesData) use ($science): bool {
            $scienceRow = collect($gradesData)->firstWhere('subject_name', $science->name);

            return $scienceRow !== null
                && $scienceRow['final_grade'] === null
                && $scienceRow['remarks'] === '';
        });
    }
}
