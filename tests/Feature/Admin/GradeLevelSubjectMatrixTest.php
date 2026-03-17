<?php

namespace Tests\Feature\Admin;

use App\Models\GradeLevel;
use App\Models\GradeLevelSubject;
use App\Models\Role;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class GradeLevelSubjectMatrixTest extends TestCase
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

    public function test_subjects_by_grade_level_returns_active_matrix_assignments(): void
    {
        $adminUser = $this->createAdminUser();

        $gradeLevel = GradeLevel::factory()->level(4)->create();
        $legacyGradeLevel = GradeLevel::factory()->level(3)->create();

        $matrixSubject = Subject::create([
            'name' => 'Matrix Subject',
            'code' => 'MTRX-101',
            'description' => 'Assigned via matrix only',
            'grade_level_id' => $legacyGradeLevel->id,
            'is_active' => true,
        ]);

        $unassignedSubject = Subject::create([
            'name' => 'Unassigned Subject',
            'code' => 'UNAS-101',
            'description' => 'Not assigned to selected grade',
            'grade_level_id' => $legacyGradeLevel->id,
            'is_active' => true,
        ]);

        GradeLevelSubject::create([
            'grade_level_id' => $gradeLevel->id,
            'subject_id' => $matrixSubject->id,
            'is_active' => true,
            'written_works_weight' => 40,
            'performance_tasks_weight' => 40,
            'quarterly_assessments_weight' => 20,
        ]);

        GradeLevelSubject::create([
            'grade_level_id' => $gradeLevel->id,
            'subject_id' => $unassignedSubject->id,
            'is_active' => false,
            'written_works_weight' => 40,
            'performance_tasks_weight' => 40,
            'quarterly_assessments_weight' => 20,
        ]);

        $response = $this->actingAs($adminUser)
            ->getJson(route('admin.subjects.by_grade_level', ['gradeLevel' => $gradeLevel->id]));

        $response
            ->assertOk()
            ->assertJsonFragment(['id' => $matrixSubject->id, 'name' => 'Matrix Subject'])
            ->assertJsonMissing(['id' => $unassignedSubject->id, 'name' => 'Unassigned Subject']);
    }

    public function test_store_reactivates_inactive_grade_level_subject_assignment(): void
    {
        $adminUser = $this->createAdminUser();

        $gradeLevel = GradeLevel::factory()->level(5)->create();
        $legacyGradeLevel = GradeLevel::factory()->level(2)->create();

        $subject = Subject::create([
            'name' => 'Reactivatable Subject',
            'code' => 'REAC-101',
            'description' => 'Inactive assignment should reactivate',
            'grade_level_id' => $legacyGradeLevel->id,
            'is_active' => true,
        ]);

        $existing = GradeLevelSubject::create([
            'grade_level_id' => $gradeLevel->id,
            'subject_id' => $subject->id,
            'is_active' => false,
            'written_works_weight' => 30,
            'performance_tasks_weight' => 40,
            'quarterly_assessments_weight' => 30,
        ]);

        $response = $this->actingAs($adminUser)
            ->postJson(route('admin.subject-assignments.store'), [
                'grade_level_id' => $gradeLevel->id,
                'subject_id' => $subject->id,
            ]);

        $response->assertOk();
        $response->assertJsonPath('grade_level_subject.id', $existing->id);

        $this->assertDatabaseHas('grade_level_subjects', [
            'id' => $existing->id,
            'is_active' => true,
            'written_works_weight' => 30,
            'performance_tasks_weight' => 40,
            'quarterly_assessments_weight' => 30,
        ]);

        $this->assertSame(
            1,
            GradeLevelSubject::query()
                ->where('grade_level_id', $gradeLevel->id)
                ->where('subject_id', $subject->id)
                ->count()
        );
    }
}
