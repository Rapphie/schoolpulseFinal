<?php

namespace Tests\Feature\Teacher;

use App\Models\GradeLevel;
use App\Models\Guardian;
use App\Models\Role;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class TeacherStudentProfileTest extends TestCase
{
    use DatabaseTransactions;

    public function test_teacher_student_profile_creation_validates_duplicate_guardian_email(): void
    {
        [$teacher, $teacherUser] = $this->createTeacherUser();
        $guardianRole = Role::firstOrCreate(
            ['name' => 'guardian'],
            ['description' => 'Guardian role']
        );
        $existingGuardian = User::factory()->create([
            'role_id' => $guardianRole->id,
            'email' => 'existing.guardian.'.Str::lower(Str::random(8)).'@example.com',
        ]);
        $gradeLevel = GradeLevel::create([
            'name' => 'Grade 1',
            'level' => 1,
            'description' => 'Test grade level',
        ]);

        $response = $this->actingAs($teacherUser)
            ->from(route('teacher.students.create'))
            ->post(route('teacher.students.store'), [
                'lrn' => '202603030001',
                'first_name' => 'Profile',
                'last_name' => 'Student',
                'gender' => 'female',
                'birthdate' => '2016-03-15',
                'address' => '123 Test Street',
                'guardian_first_name' => 'Parent',
                'guardian_last_name' => 'Duplicate',
                'guardian_email' => $existingGuardian->email,
                'guardian_phone' => '09123456789',
                'guardian_relationship' => 'parent',
                'grade_level_id' => $gradeLevel->id,
            ]);

        $response->assertRedirect(route('teacher.students.create'));
        $response->assertSessionHasErrors(['guardian_email']);
    }

    public function test_teacher_can_clear_guardian_contact_details_when_updating_student_profile(): void
    {
        [$teacher, $teacherUser] = $this->createTeacherUser();
        $guardianRole = Role::firstOrCreate(
            ['name' => 'guardian'],
            ['description' => 'Guardian role']
        );
        $guardianUser = User::factory()->create([
            'role_id' => $guardianRole->id,
            'email' => 'teacher.clear.guardian.'.Str::lower(Str::random(8)).'@example.com',
            'first_name' => 'Clear',
            'last_name' => 'Guardian',
        ]);
        $guardian = Guardian::create([
            'user_id' => $guardianUser->id,
            'phone' => '09175551111',
            'relationship' => 'parent',
        ]);
        $student = Student::create([
            'student_id' => Student::generateStudentId(),
            'first_name' => 'Update',
            'last_name' => 'Student',
            'gender' => 'male',
            'birthdate' => '2016-03-15',
            'guardian_id' => $guardian->id,
            'enrollment_date' => now(),
        ]);

        $response = $this->actingAs($teacherUser)
            ->put(route('teacher.students.update', $student), [
                'lrn' => '',
                'first_name' => 'Update',
                'last_name' => 'Student',
                'gender' => 'male',
                'birthdate' => '2016-03-15',
                'address' => '',
                'distance_km' => '',
                'transportation' => '',
                'family_income' => '',
                'guardian_first_name' => 'Clear',
                'guardian_last_name' => 'Guardian',
                'guardian_email' => '',
                'guardian_phone' => '',
                'guardian_relationship' => 'parent',
            ]);

        $response->assertRedirect(route('teacher.students.show', $student));
        $response->assertSessionHas('success', 'Student profile updated successfully.');

        $guardianUser->refresh();
        $guardian->refresh();

        $this->assertNull($guardianUser->email);
        $this->assertNull($guardian->phone);
    }

    /**
     * @return array{Teacher, User}
     */
    private function createTeacherUser(): array
    {
        $teacherRole = Role::firstOrCreate(
            ['name' => 'teacher'],
            ['description' => 'Teacher role']
        );

        $user = User::factory()->create([
            'role_id' => $teacherRole->id,
            'password' => Hash::make('password'),
        ]);

        $teacher = Teacher::create([
            'user_id' => $user->id,
            'department' => 'General',
            'status' => 'active',
        ]);

        return [$teacher, $user];
    }
}
