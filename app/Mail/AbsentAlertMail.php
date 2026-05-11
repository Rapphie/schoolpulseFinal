<?php

namespace App\Mail;

use App\Models\Student;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AbsentAlertMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $student;

    public $consecutiveAbsences;

    public $recipientName;

    public function __construct(
        Student $student,
        $consecutiveAbsences,
        string $recipientName,
    ) {
        $this->student = $student;
        $this->consecutiveAbsences = $consecutiveAbsences;
        $this->recipientName = $recipientName;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('Student Absent Alert')->markdown(
            'emails.absent_alert',
        );
    }
}
