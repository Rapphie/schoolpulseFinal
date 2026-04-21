<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WelcomeEmail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $user;

    public $password;

    /**
     * Create a new message instance.
     */
    public function __construct(User $user, string $password)
    {
        $this->user = $user;
        $this->password = $password;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Welcome to SchoolPulse - Your Account Details",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(view: "emails.welcome-email");
    }

    /**
     * Get the message attachments.
     */
    public function attachments(): array
    {
        return [];
    }
}
