<?php

namespace Tests\Feature\Teacher;

use App\Http\Controllers\Teacher\TeacherAttendanceController;
use App\Mail\AbsentAlertMail;
use App\Models\Attendance;
use App\Models\Enrollment;
use App\Models\Schedule;
use App\Models\Student;
use App\Models\Teacher;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class CheckAbsencesTest extends TestCase
{
    use DatabaseTransactions;

    public function test_check_absences_sends_alert_after_three_consecutive_absences(): void
    {
        Mail::fake();
        Log::spy();
        config(['cache.default' => 'array']);
        Carbon::setTestNow(Carbon::parse('2025-12-10 08:00:00'));

        $student = Student::find(2);

        if (! $student) {
            $this->markTestSkipped('Student id 2 not found.');
        }

        $guardian = $student->guardian;

        if (! $guardian || ! $guardian->user) {
            $this->markTestSkipped('Student id 2 is missing a guardian with a user account.');
        }

        $guardianEmail = $guardian->user->email;
        if (! $guardianEmail) {
            $this->markTestSkipped('Guardian for student id 2 has no email address.');
        }

        $enrollment = Enrollment::where('student_id', $student->id)->latest()->first();

        if (! $enrollment) {
            $this->markTestSkipped('Student id 2 has no enrollment records.');
        }

        $class = $enrollment->class;
        if (! $class) {
            $this->markTestSkipped('Enrollment for student id 2 has no class assigned.');
        }

        $schoolYearId = $enrollment->school_year_id ?? $class->school_year_id;
        if (! $schoolYearId) {
            $this->markTestSkipped('Could not resolve school year for student id 2.');
        }

        $teacherId = $enrollment->teacher_id ?? $class->teacher_id;
        if (! $teacherId) {
            $this->markTestSkipped('Student id 2 does not have an assigned teacher.');
        }

        $teacher = Teacher::find($teacherId);
        if (! $teacher || ! $teacher->user) {
            $this->markTestSkipped('Teacher for student id 2 is missing a linked user.');
        }

        $teacherEmail = $teacher->user->email;
        if (! $teacherEmail) {
            $this->markTestSkipped('Teacher for student id 2 has no email address.');
        }

        if ($guardianEmail === $teacherEmail) {
            $this->markTestSkipped('Guardian and teacher share the same email — cannot test dual delivery.');
        }

        $schedule = Schedule::where('class_id', $class->id)->orderBy('id')->first();
        if (! $schedule) {
            $this->markTestSkipped('No subject schedule found for student id 2.');
        }

        $subjectId = $schedule->subject_id;
        if (! $subjectId) {
            $this->markTestSkipped('Schedule for student id 2 has no subject.');
        }

        Attendance::where('student_id', $student->id)
            ->whereIn('date', [Carbon::now()->toDateString(), Carbon::now()->subDay()->toDateString(), Carbon::now()->subDays(2)->toDateString()])
            ->delete();

        foreach ([0, 1, 2] as $offset) {
            Attendance::create([
                'student_id' => $student->id,
                'class_id' => $class->id,
                'subject_id' => $subjectId,
                'teacher_id' => $teacher->id,
                'status' => 'absent',
                'date' => Carbon::now()->subDays($offset)->toDateString(),
                'quarter' => '1',
                'school_year_id' => $schoolYearId,
            ]);
        }

        $this->assertSame(3, Attendance::where('student_id', $student->id)
            ->where('status', 'absent')
            ->whereIn('date', [Carbon::now()->toDateString(), Carbon::now()->subDay()->toDateString(), Carbon::now()->subDays(2)->toDateString()])
            ->count());

        $cacheKey = 'absent_alert_sent_'.$student->id;
        $this->assertNull(cache($cacheKey));

        $controller = app(TeacherAttendanceController::class);
        $method = new \ReflectionMethod(TeacherAttendanceController::class, 'checkAbsences');
        $method->setAccessible(true);
        $method->invoke($controller, $student->id, $teacher->id);

        Mail::assertQueued(AbsentAlertMail::class, 2);
        Mail::assertQueued(AbsentAlertMail::class, fn (AbsentAlertMail $mail) => $mail->hasTo($teacherEmail));
        Mail::assertQueued(AbsentAlertMail::class, fn (AbsentAlertMail $mail) => $mail->hasTo($guardianEmail));

        $this->assertNotNull(cache($cacheKey));
        Log::shouldNotHaveReceived('error');

        Carbon::setTestNow();
    }
}
