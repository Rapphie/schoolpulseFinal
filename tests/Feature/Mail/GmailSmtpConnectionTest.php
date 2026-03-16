<?php

namespace Tests\Feature\Mail;

use Illuminate\Mail\Mailer;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;
use Tests\TestCase;

class GmailSmtpConnectionTest extends TestCase
{
    public function test_gmail_smtp_connection_can_be_established(): void
    {
        $shouldTestRaw = $_ENV['GMAIL_SMTP_TEST_ENABLED'] ?? $_SERVER['GMAIL_SMTP_TEST_ENABLED'] ?? null;
        $shouldTest = filter_var($shouldTestRaw, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);

        if ($shouldTest !== true) {
            $this->markTestSkipped('Set GMAIL_SMTP_TEST_ENABLED=true to run Gmail SMTP connection test.');
        }

        $host = (string) config('mail.mailers.smtp.host');
        $port = (int) config('mail.mailers.smtp.port');
        $username = (string) config('mail.mailers.smtp.username');
        $password = (string) config('mail.mailers.smtp.password');

        $this->assertSame('smtp.gmail.com', $host, 'MAIL_HOST must be smtp.gmail.com for this test.');
        $this->assertContains($port, [465, 587], 'MAIL_PORT must be 465 or 587 for Gmail SMTP.');
        $this->assertNotEmpty($username, 'MAIL_USERNAME is required to authenticate with Gmail SMTP.');
        $this->assertNotEmpty($password, 'MAIL_PASSWORD is required to authenticate with Gmail SMTP.');

        /** @var Mailer $mailer */
        $mailer = Mail::mailer('smtp');
        $transport = $mailer->getSymfonyTransport();

        if (! $transport instanceof EsmtpTransport) {
            $this->markTestSkipped('Configured transport is not an SMTP transport; skipping Gmail connection test.');
        }

        /** @var EsmtpTransport $smtpTransport */
        $smtpTransport = $transport;

        $started = false;

        try {
            $smtpTransport->start();
            $started = true;
            $this->addToAssertionCount(1);
        } catch (TransportExceptionInterface $exception) {
            $this->fail('Unable to connect to Gmail SMTP: '.$exception->getMessage());
        } finally {
            if ($started) {
                $smtpTransport->stop();
            }
        }
    }
}
