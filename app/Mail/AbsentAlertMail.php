<?php

namespace App\Mail;

use App\Models\Student;
use App\Models\Teacher;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AbsentAlertMail extends Mailable
{
    use Queueable, SerializesModels;

    public $student;
    public $teacher;
    public $consecutiveAbsences;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(Student $student, Teacher $teacher, $consecutiveAbsences)
    {
        $this->student = $student;
        $this->teacher = $teacher;
        $this->consecutiveAbsences = $consecutiveAbsences;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('Student Absent Alert')
            ->markdown('emails.absent_alert');
    }
}