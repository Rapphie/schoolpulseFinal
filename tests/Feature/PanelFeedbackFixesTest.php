<?php

namespace Tests\Feature;

use App\Models\Assessment;
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
use App\Services\StudentProfileService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class PanelFeedbackFixesTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * Helper to get or skip test dependencies.
     *
     * @return array{teacher: Teacher, schoolYear: SchoolYear}
     */
    private function getBaseTestData(): array
    {
        $activeSchoolYear = SchoolYear::where('is_active', true)->first();
        if (! $activeSchoolYear) {
            $this->markTestSkipped('No active school year found.');
        }

        $teacher = Teacher::whereHas('user')->first();
        if (! $teacher) {
            $this->markTestSkipped('No teacher with a user account found.');
        }

        return [
            'teacher' => $teacher,
            'schoolYear' => $activeSchoolYear,
        ];
    }

    // ========================================================================
    // 1. Student Profile: Auto-mark previous profile as "promoted"
    // ========================================================================

    public function test_previous_profile_is_marked_promoted_on_re_enrollment(): void
    {
        $data = $this->getBaseTestData();
        $activeSchoolYear = $data['schoolYear'];

        $gradeLevel1 = GradeLevel::where('level', 1)->first();
        $gradeLevel2 = GradeLevel::where('level', 2)->first();
        if (! $gradeLevel1 || ! $gradeLevel2) {
            $this->markTestSkipped('Grade levels 1 and 2 are required.');
        }

        // Pick a student that does NOT already have an enrollment for the active school year
        $student = Student::whereDoesntHave('enrollments', function ($q) use ($activeSchoolYear) {
            $q->where('school_year_id', $activeSchoolYear->id);
        })->first();

        if (! $student) {
            // Create a fresh student if all existing ones are already enrolled
            $student = Student::create([
                'lrn' => 'TEST'.now()->timestamp,
                'first_name' => 'Test',
                'last_name' => 'PromotionStudent',
                'gender' => 'male',
                'birth_date' => '2015-01-01',
            ]);
        }

        // Create a "previous" school year and profile
        $previousSchoolYear = SchoolYear::create([
            'name' => '2024-2025-test',
            'start_date' => '2024-06-01',
            'end_date' => '2025-03-31',
            'is_active' => false,
        ]);

        $previousProfile = StudentProfile::create([
            'student_id' => $student->id,
            'school_year_id' => $previousSchoolYear->id,
            'grade_level_id' => $gradeLevel1->id,
            'status' => 'enrolled',
        ]);

        // Find a class in grade 2 for the active school year
        $class = Classes::whereHas('section.gradeLevel', function ($q) use ($gradeLevel2) {
            $q->where('id', $gradeLevel2->id);
        })->where('school_year_id', $activeSchoolYear->id)->first();

        if (! $class) {
            // Create section and class for grade 2
            $section = Section::where('grade_level_id', $gradeLevel2->id)->first();
            if (! $section) {
                $this->markTestSkipped('No section for Grade 2 found.');
            }

            $class = Classes::create([
                'section_id' => $section->id,
                'school_year_id' => $activeSchoolYear->id,
                'teacher_id' => $data['teacher']->id,
                'capacity' => 40,
            ]);
        }

        // Use the service to create enrollment (this should auto-promote the old profile)
        $service = new StudentProfileService;
        $service->createEnrollmentWithProfile([
            'student_id' => $student->id,
            'class_id' => $class->id,
            'school_year_id' => $activeSchoolYear->id,
            'teacher_id' => $data['teacher']->id,
            'enrollment_date' => now(),
            'status' => 'enrolled',
        ]);

        // Assert the previous profile was marked as "promoted"
        $previousProfile->refresh();
        $this->assertEquals('promoted', $previousProfile->status);
    }

    // ========================================================================
    // 2. Score validation: Reject scores when max_score is not set
    // ========================================================================

    public function test_save_grades_rejects_when_max_score_is_null(): void
    {
        $data = $this->getBaseTestData();
        $teacher = $data['teacher'];
        $activeSchoolYear = $data['schoolYear'];

        $class = Classes::where('school_year_id', $activeSchoolYear->id)
            ->where('teacher_id', $teacher->id)
            ->first();

        if (! $class) {
            $this->markTestSkipped('No class found for this teacher.');
        }

        $student = $class->students()->first();
        if (! $student) {
            $this->markTestSkipped('No students enrolled in teacher class.');
        }

        // Create assessment with null max_score
        $assessment = Assessment::create([
            'class_id' => $class->id,
            'subject_id' => Schedule::where('class_id', $class->id)->value('subject_id') ?? Subject::first()->id,
            'teacher_id' => $teacher->id,
            'school_year_id' => $activeSchoolYear->id,
            'name' => 'Test Assessment No Max',
            'type' => 'written_works',
            'max_score' => null,
            'quarter' => 1,
            'assessment_date' => now()->toDateString(),
        ]);

        // Try to save grades via AJAX
        $response = $this->actingAs($teacher->user)->postJson(
            route('teacher.assessments.saveGrades', $class),
            [
                'grades' => [
                    [
                        'student_id' => $student->id,
                        'assessment_id' => $assessment->id,
                        'score' => 5,
                    ],
                ],
            ]
        );

        $response->assertStatus(422);
        $response->assertJsonFragment(['success' => false]);
    }

    // ========================================================================
    // 3. Score validation: Reject scores exceeding max_score
    // ========================================================================

    public function test_save_grades_rejects_score_exceeding_max(): void
    {
        $data = $this->getBaseTestData();
        $teacher = $data['teacher'];
        $activeSchoolYear = $data['schoolYear'];

        $class = Classes::where('school_year_id', $activeSchoolYear->id)
            ->where('teacher_id', $teacher->id)
            ->first();

        if (! $class) {
            $this->markTestSkipped('No class found for this teacher.');
        }

        $student = $class->students()->first();
        if (! $student) {
            $this->markTestSkipped('No students enrolled in teacher class.');
        }

        $assessment = Assessment::create([
            'class_id' => $class->id,
            'subject_id' => Schedule::where('class_id', $class->id)->value('subject_id') ?? Subject::first()->id,
            'teacher_id' => $teacher->id,
            'school_year_id' => $activeSchoolYear->id,
            'name' => 'Test Assessment Max 10',
            'type' => 'written_works',
            'max_score' => 10,
            'quarter' => 1,
            'assessment_date' => now()->toDateString(),
        ]);

        // Score exceeds max
        $response = $this->actingAs($teacher->user)->postJson(
            route('teacher.assessments.saveGrades', $class),
            [
                'grades' => [
                    [
                        'student_id' => $student->id,
                        'assessment_id' => $assessment->id,
                        'score' => 12,
                    ],
                ],
            ]
        );

        $response->assertStatus(422);
        $response->assertJsonFragment(['success' => false]);
    }

    // ========================================================================
    // 4. ScheduleController: Block schedule creation for Grades 1-3
    // ========================================================================

    public function test_schedule_controller_blocks_grade_1_3_schedule_creation(): void
    {
        $data = $this->getBaseTestData();
        $activeSchoolYear = $data['schoolYear'];

        $adminUser = User::where('role_id', 1)->first();
        if (! $adminUser) {
            $this->markTestSkipped('No admin user found.');
        }

        $gradeLevel = GradeLevel::whereIn('level', [1, 2, 3])->first();
        if (! $gradeLevel) {
            $this->markTestSkipped('No grade level 1-3 found.');
        }

        $class = Classes::whereHas('section.gradeLevel', function ($q) use ($gradeLevel) {
            $q->where('id', $gradeLevel->id);
        })->where('school_year_id', $activeSchoolYear->id)->first();

        if (! $class) {
            $this->markTestSkipped('No Grades 1-3 class found.');
        }

        $subject = Subject::where('grade_level_id', $gradeLevel->id)->first();
        if (! $subject) {
            $this->markTestSkipped('No subject found for this grade level.');
        }

        $teacher = Teacher::first();

        $response = $this->actingAs($adminUser)->post(route('admin.schedules.store'), [
            'class_id' => $class->id,
            'subject_id' => $subject->id,
            'teacher_id' => $teacher->id,
            'day_of_week' => ['monday'],
            'start_time' => '08:00',
            'end_time' => '09:00',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    // ========================================================================
    // 5. ScheduleController: Block adviser gets rejected for new schedule
    // ========================================================================

    public function test_schedule_controller_blocks_block_adviser_assignment(): void
    {
        $data = $this->getBaseTestData();
        $activeSchoolYear = $data['schoolYear'];

        $adminUser = User::where('role_id', 1)->first();
        if (! $adminUser) {
            $this->markTestSkipped('No admin user found.');
        }

        // Find a teacher who is a block adviser (Grades 1-3)
        $blockAdviserClass = Classes::where('school_year_id', $activeSchoolYear->id)
            ->whereNotNull('teacher_id')
            ->whereHas('section.gradeLevel', function ($q) {
                $q->whereIn('level', [1, 2, 3]);
            })
            ->first();

        if (! $blockAdviserClass) {
            $this->markTestSkipped('No block adviser class found.');
        }

        $blockTeacher = Teacher::find($blockAdviserClass->teacher_id);

        // Try to assign this block adviser to a Grade 4-6 class
        $upperGradeClass = Classes::where('school_year_id', $activeSchoolYear->id)
            ->whereHas('section.gradeLevel', function ($q) {
                $q->whereIn('level', [4, 5, 6]);
            })
            ->first();

        if (! $upperGradeClass) {
            $this->markTestSkipped('No upper grade class found.');
        }

        $subject = Subject::whereHas('gradeLevel', function ($q) {
            $q->whereIn('level', [4, 5, 6]);
        })->first();

        if (! $subject) {
            $this->markTestSkipped('No upper grade subject found.');
        }

        $response = $this->actingAs($adminUser)->post(route('admin.schedules.store'), [
            'class_id' => $upperGradeClass->id,
            'subject_id' => $subject->id,
            'teacher_id' => $blockTeacher->id,
            'day_of_week' => ['monday'],
            'start_time' => '13:00',
            'end_time' => '14:00',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    // ========================================================================
    // 6. Attendance: All Day toggle only for Grades 1-3 advisers
    // ========================================================================

    public function test_attendance_page_hides_all_day_toggle_for_grade_4_6_adviser(): void
    {
        $data = $this->getBaseTestData();
        $activeSchoolYear = $data['schoolYear'];

        // Find a teacher who is adviser for a Grade 4-6 class but not for Grades 1-3
        $upperGradeAdviserClass = Classes::where('school_year_id', $activeSchoolYear->id)
            ->whereNotNull('teacher_id')
            ->whereHas('section.gradeLevel', function ($q) {
                $q->whereIn('level', [4, 5, 6]);
            })
            ->first();

        if (! $upperGradeAdviserClass) {
            $this->markTestSkipped('No Grade 4-6 advisory class found.');
        }

        $teacher = Teacher::find($upperGradeAdviserClass->teacher_id);

        // Ensure this teacher is NOT also a block adviser for Grades 1-3
        $isAlsoBlockAdviser = Classes::where('teacher_id', $teacher->id)
            ->where('school_year_id', $activeSchoolYear->id)
            ->whereHas('section.gradeLevel', function ($q) {
                $q->whereIn('level', [1, 2, 3]);
            })
            ->exists();

        if ($isAlsoBlockAdviser) {
            $this->markTestSkipped('Teacher is also a block adviser; cannot test isolation.');
        }

        // Ensure teacher has a schedule so the page loads
        $hasSchedule = Schedule::where('teacher_id', $teacher->id)
            ->whereHas('class', function ($q) use ($activeSchoolYear) {
                $q->where('school_year_id', $activeSchoolYear->id);
            })
            ->exists();

        if (! $hasSchedule) {
            $this->markTestSkipped('Teacher has no schedule for the active school year.');
        }

        $response = $this->actingAs($teacher->user)->get(route('teacher.attendance.take'));

        $response->assertStatus(200);
        $response->assertDontSee('Apply to All Subjects of the Day');
    }

    public function test_attendance_page_shows_all_day_toggle_for_grade_1_3_adviser(): void
    {
        $data = $this->getBaseTestData();
        $activeSchoolYear = $data['schoolYear'];

        // Find a teacher who is adviser for a Grade 1-3 class
        $blockAdviserClass = Classes::where('school_year_id', $activeSchoolYear->id)
            ->whereNotNull('teacher_id')
            ->whereHas('section.gradeLevel', function ($q) {
                $q->whereIn('level', [1, 2, 3]);
            })
            ->first();

        if (! $blockAdviserClass) {
            $this->markTestSkipped('No Grade 1-3 advisory class found.');
        }

        $teacher = Teacher::find($blockAdviserClass->teacher_id);

        $hasSchedule = Schedule::where('teacher_id', $teacher->id)
            ->whereHas('class', function ($q) use ($activeSchoolYear) {
                $q->where('school_year_id', $activeSchoolYear->id);
            })
            ->exists();

        if (! $hasSchedule) {
            $this->markTestSkipped('Block adviser has no schedule.');
        }

        $response = $this->actingAs($teacher->user)->get(route('teacher.attendance.take'));

        $response->assertStatus(200);
        $response->assertSee('Apply to All Subjects of the Day');
    }
}
