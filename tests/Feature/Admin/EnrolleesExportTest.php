<?php

namespace Tests\Feature\Admin;

use App\Exports\EnrolleesExport;
use App\Models\Classes;
use App\Models\Enrollment;
use App\Models\GradeLevel;
use App\Models\Guardian;
use App\Models\SchoolYear;
use App\Models\Section;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class EnrolleesExportTest extends TestCase
{
    use DatabaseTransactions;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::where('role_id', 1)->first()
            ?? User::factory()->create(['role_id' => 1]);
    }

    public function test_enrollees_export_csv_includes_enrolled_by_column_and_value(): void
    {
        $schoolYear = SchoolYear::where('is_active', true)->first()
            ?? SchoolYear::create([
                'name' => '2099-2100',
                'start_date' => '2099-06-01',
                'end_date' => '2100-03-31',
                'is_active' => true,
            ]);

        $gradeLevel = GradeLevel::first()
            ?? GradeLevel::create([
                'name' => 'Grade 1',
                'level' => 1,
            ]);

        $section = Section::create([
            'name' => 'EXP-SEC',
            'grade_level_id' => $gradeLevel->id,
            'description' => 'Export test section',
        ]);

        $teacherUser = User::factory()->create([
            'role_id' => 2,
            'first_name' => 'Enroll',
            'last_name' => 'Teacher',
        ]);

        $teacher = Teacher::create([
            'user_id' => $teacherUser->id,
            'phone' => '09123456789',
            'gender' => 'male',
            'date_of_birth' => '1985-01-01',
            'status' => 'active',
        ]);

        $class = Classes::create([
            'section_id' => $section->id,
            'school_year_id' => $schoolYear->id,
            'teacher_id' => $teacher->id,
            'capacity' => 40,
        ]);

        $guardianUser = User::factory()->create(['role_id' => 3]);
        $guardian = Guardian::create([
            'user_id' => $guardianUser->id,
            'phone' => 9123456789,
            'relationship' => 'parent',
        ]);

        $student = Student::create([
            'student_id' => 'EXP-001',
            'lrn' => '123456789012',
            'first_name' => 'Sample',
            'last_name' => 'Student',
            'gender' => 'female',
            'birthdate' => '2015-01-01',
            'guardian_id' => $guardian->id,
            'enrollment_date' => now()->toDateString(),
        ]);

        Enrollment::create([
            'student_id' => $student->id,
            'class_id' => $class->id,
            'school_year_id' => $schoolYear->id,
            'teacher_id' => $teacher->id,
            'enrollment_date' => now()->toDateString(),
            'status' => 'enrolled',
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.reports.export.enrollees', [
            'format' => 'csv',
            'school_year_id' => $schoolYear->id,
        ]));

        $response->assertStatus(200);
        $csvContent = $response->streamedContent();

        $this->assertStringContainsString('Enrolled By', $csvContent);
        $this->assertStringContainsString('Enroll Teacher', $csvContent);
        $this->assertStringContainsString('Sample Student', $csvContent);
    }

    public function test_enrollees_export_collection_can_filter_by_enrolled_by_user_id(): void
    {
        $schoolYear = SchoolYear::where('is_active', true)->first()
            ?? SchoolYear::create([
                'name' => '2099-2100',
                'start_date' => '2099-06-01',
                'end_date' => '2100-03-31',
                'is_active' => true,
            ]);

        $gradeLevel = GradeLevel::first()
            ?? GradeLevel::create([
                'name' => 'Grade 1',
                'level' => 1,
            ]);

        $section = Section::create([
            'name' => 'EXP-SEC-2',
            'grade_level_id' => $gradeLevel->id,
            'description' => 'Export test section 2',
        ]);

        $teacherUser = User::factory()->create([
            'role_id' => 2,
            'first_name' => 'Class',
            'last_name' => 'Teacher',
        ]);

        $teacher = Teacher::create([
            'user_id' => $teacherUser->id,
            'phone' => '09123456780',
            'gender' => 'male',
            'date_of_birth' => '1980-01-01',
            'status' => 'active',
        ]);

        $class = Classes::create([
            'section_id' => $section->id,
            'school_year_id' => $schoolYear->id,
            'teacher_id' => $teacher->id,
            'capacity' => 40,
        ]);

        $otherAdmin = User::factory()->create(['role_id' => 1]);

        $guardianOneUser = User::factory()->create(['role_id' => 3]);
        $guardianOne = Guardian::create([
            'user_id' => $guardianOneUser->id,
            'phone' => 9123456701,
            'relationship' => 'parent',
        ]);

        $studentOne = Student::create([
            'student_id' => 'EXP-101',
            'lrn' => '123456789013',
            'first_name' => 'Mine',
            'last_name' => 'Student',
            'gender' => 'female',
            'birthdate' => '2015-01-01',
            'guardian_id' => $guardianOne->id,
            'enrollment_date' => now()->toDateString(),
        ]);

        $guardianTwoUser = User::factory()->create(['role_id' => 3]);
        $guardianTwo = Guardian::create([
            'user_id' => $guardianTwoUser->id,
            'phone' => 9123456702,
            'relationship' => 'parent',
        ]);

        $studentTwo = Student::create([
            'student_id' => 'EXP-102',
            'lrn' => '123456789014',
            'first_name' => 'Other',
            'last_name' => 'Student',
            'gender' => 'male',
            'birthdate' => '2015-01-01',
            'guardian_id' => $guardianTwo->id,
            'enrollment_date' => now()->toDateString(),
        ]);

        Enrollment::create([
            'student_id' => $studentOne->id,
            'class_id' => $class->id,
            'school_year_id' => $schoolYear->id,
            'teacher_id' => $teacher->id,
            'enrolled_by_user_id' => $this->admin->id,
            'enrollment_date' => now()->toDateString(),
            'status' => 'enrolled',
        ]);

        Enrollment::create([
            'student_id' => $studentTwo->id,
            'class_id' => $class->id,
            'school_year_id' => $schoolYear->id,
            'teacher_id' => $teacher->id,
            'enrolled_by_user_id' => $otherAdmin->id,
            'enrollment_date' => now()->toDateString(),
            'status' => 'enrolled',
        ]);

        $export = new EnrolleesExport(null, null, $schoolYear->id, $this->admin->id);
        $rows = $export->collection();

        $this->assertCount(1, $rows);
        $this->assertSame($studentOne->id, $rows->first()->student_id);
        $this->assertSame($this->admin->id, $rows->first()->enrolled_by_user_id);
    }
}
