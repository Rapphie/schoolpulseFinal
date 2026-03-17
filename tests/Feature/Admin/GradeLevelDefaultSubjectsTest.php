<?php

namespace Tests\Feature\Admin;

use App\Http\Controllers\Admin\GradeLevelController;
use App\Models\GradeLevel;
use App\Models\GradeLevelSubject;
use App\Models\Role;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use ReflectionClass;
use Tests\TestCase;

class GradeLevelDefaultSubjectsTest extends TestCase
{
    use DatabaseTransactions;

    private function ensureRole(string $name, int $id): Role
    {
        return Role::firstOrCreate(['id' => $id], ['name' => $name, 'description' => ucfirst($name)]);
    }

    private function createAdminUser(): User
    {
        $this->ensureRole('admin', 1);

        return User::factory()->create([
            'role_id' => 1,
            'temporary_password' => null,
        ]);
    }

    private function invokePrivate(object $instance, string $method, array $args = []): mixed
    {
        $reflection = new ReflectionClass($instance);
        $reflectionMethod = $reflection->getMethod($method);
        $reflectionMethod->setAccessible(true);

        return $reflectionMethod->invokeArgs($instance, $args);
    }

    public function test_default_subject_mapping_matches_matatag_reference(): void
    {
        $this->createAdminUser();

        $controller = app(GradeLevelController::class);

        $expectedByLevel = [
            1 => ['Language', 'Reading and Literacy', 'Mathematics', 'Makabansa', 'GMRC'],
            2 => ['Filipino', 'English', 'Mathematics', 'Makabansa', 'GMRC'],
            3 => ['Filipino', 'English', 'Science', 'Mathematics', 'Makabansa', 'GMRC'],
            4 => ['Filipino', 'English', 'Science', 'Mathematics', 'AP', 'Music', 'Arts', 'Physical Education', 'Health', 'EPP', 'GMRC'],
            5 => ['Filipino', 'English', 'Science', 'Mathematics', 'AP', 'Music', 'Arts', 'Physical Education', 'Health', 'EPP', 'GMRC'],
            6 => ['Filipino', 'English', 'Science', 'Mathematics', 'AP', 'Music', 'Arts', 'Physical Education', 'Health', 'TLE', 'ESP'],
        ];

        foreach ($expectedByLevel as $level => $expectedSubjects) {
            $actualSubjects = $this->invokePrivate($controller, 'defaultSubjectsByLevel', [$level]);

            $this->assertEqualsCanonicalizing(
                $expectedSubjects,
                $actualSubjects,
                "Default subjects do not match MATATAG mapping for grade level {$level}."
            );
        }
    }

    public function test_assign_default_subjects_creates_matrix_rows_for_grade_level(): void
    {
        $this->createAdminUser();

        $controller = app(GradeLevelController::class);

        $gradeLevel = GradeLevel::create([
            'name' => 'Grade Default Subject Test',
            'level' => 99,
            'description' => 'Temporary test level',
        ]);

        $gradeLevel->level = 4;

        $this->invokePrivate($controller, 'assignDefaultSubjects', [$gradeLevel]);

        $expectedSubjects = ['Filipino', 'English', 'Science', 'Mathematics', 'AP', 'Music', 'Arts', 'Physical Education', 'Health', 'EPP', 'GMRC'];

        $actualSubjects = GradeLevelSubject::where('grade_level_id', $gradeLevel->id)
            ->with('subject')
            ->get()
            ->pluck('subject.name')
            ->all();

        $this->assertEqualsCanonicalizing($expectedSubjects, $actualSubjects);

        foreach ($expectedSubjects as $subjectName) {
            $this->assertNotNull(Subject::where('name', $subjectName)->first());
        }
    }
}
