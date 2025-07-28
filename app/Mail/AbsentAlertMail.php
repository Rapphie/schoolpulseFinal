<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\Student;
use App\Models\Teacher;

class AbsentAlertMail extends Mailable
{
    use Queueable, SerializesModels;

    public $student;
    public $teacher;
    public $absenceCount;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(Student $student, Teacher $teacher, $absenceCount)
    {
        $this->student = $student;
        $this->teacher = $teacher;
        $this->absenceCount = $absenceCount;
    }

    /**
     * Get the message envelope.
     *
     * @return \Illuminate\Mail\Mailables\Envelope
     */
    public function envelope()
    {
        return new Envelope(
            subject: 'Absent Alert',
        );
    }

    /**
     * Get the message content definition.
     *
     * @return \Illuminate\Mail\Mailables\Content
     */
    public function content()
    {
        return new Content(
            view: 'emails.absent-alert',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array
     */
    public function attachments()
    {
        return [];
    }
}
