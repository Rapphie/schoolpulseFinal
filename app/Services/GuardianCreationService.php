<?php

namespace App\Services;

use App\Models\Classes;
use App\Models\Guardian;
use App\Models\Role;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class GuardianCreationService
{
    private const DEFAULT_ENROLLMENT_STATUS = 'enrolled';

    public function __construct(
        private StudentProfileService $profileService
    ) {}

    public function createGuardianWithStudent(array $validated, string $plainPassword, ?string $email = null): array
    {
        $guardianUser = User::create([
            'first_name' => $validated['guardian_first_name'],
            'last_name' => $validated['guardian_last_name'],
            'email' => $validated['guardian_email'] ?? null,
            'password' => Hash::make($plainPassword),
            'role_id' => Role::GUARDIAN_ID,
        ]);

        $guardian = Guardian::create([
            'user_id' => $guardianUser->id,
            'phone' => $validated['guardian_phone'] ?? null,
            'relationship' => $validated['guardian_relationship'],
        ]);

        $student = Student::create([
            'lrn' => $validated['lrn'] ?? null,
            'student_id' => Student::generateStudentId(),
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'gender' => $validated['gender'],
            'birthdate' => $validated['birthdate'],
            'address' => $validated['address'] ?? null,
            'distance_km' => $validated['distance_km'] ?? null,
            'transportation' => $validated['transportation'] ?? null,
            'family_income' => $validated['family_income'] ?? null,
            'guardian_id' => $guardian->id,
        ]);

        return [
            'guardianUser' => $guardianUser,
            'guardian' => $guardian,
            'student' => $student,
            'guardianUserWasCreated' => true,
        ];
    }

    public function useExistingGuardian(int $guardianId): array
    {
        $guardian = Guardian::query()
            ->with([
                'user',
                'students:id,guardian_id,first_name,last_name',
            ])
            ->findOrFail($guardianId);

        $guardianUser = $guardian->user;
        if (! $guardianUser) {
            throw new \RuntimeException('Guardian account is incomplete. Missing user profile.');
        }

        $connectedStudent = $guardian->students->first();
        $connectedStudentName = null;
        if ($connectedStudent) {
            $connectedStudentName = trim($connectedStudent->first_name.' '.$connectedStudent->last_name);
        }

        return [
            'guardian' => $guardian,
            'guardianUser' => $guardianUser,
            'connectedStudentName' => $connectedStudentName,
            'guardianUserWasCreated' => false,
        ];
    }

    public function updateGuardianFromValidated(array $validated, Guardian $guardian, User $guardianUser): void
    {
        $guardianUser->update([
            'first_name' => $validated['guardian_first_name'],
            'last_name' => $validated['guardian_last_name'],
        ]);

        $guardian->update([
            'phone' => $validated['guardian_phone'] ?? $guardian->phone,
            'relationship' => $validated['guardian_relationship'],
        ]);
    }

    public function createStudentForGuardian(array $validated, Guardian $guardian): Student
    {
        return Student::create([
            'lrn' => $validated['lrn'] ?? null,
            'student_id' => Student::generateStudentId(),
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'gender' => $validated['gender'],
            'birthdate' => $validated['birthdate'],
            'address' => $validated['address'] ?? null,
            'distance_km' => $validated['distance_km'] ?? null,
            'transportation' => $validated['transportation'] ?? null,
            'family_income' => $validated['family_income'] ?? null,
            'guardian_id' => $guardian->id,
        ]);
    }

    public function enrollStudentToClass(Student $student, Classes $class, int $teacherId, int $userId, string $status = self::DEFAULT_ENROLLMENT_STATUS): void
    {
        $this->profileService->createEnrollmentWithProfile([
            'student_id' => $student->id,
            'class_id' => $class->id,
            'school_year_id' => $class->school_year_id,
            'teacher_id' => $teacherId,
            'enrolled_by_user_id' => $userId,
            'status' => $status,
        ]);
    }

    public function sendWelcomeEmail(User $guardianUser, string $plainPassword): void
    {
        if (! empty($guardianUser->email)) {
            Mail::to($guardianUser->email)->queue(new \App\Mail\WelcomeEmail($guardianUser, $plainPassword));
        }
    }
}
