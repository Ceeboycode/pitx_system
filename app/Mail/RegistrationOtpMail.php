<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RegistrationOtpMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  string  $otp           Plain-text 6-digit OTP (only for sending, never stored)
     * @param  string  $purpose       'account' | 'company'
     * @param  string  $recipientName Name shown in the greeting
     */
    public function __construct(
        public readonly string $otp,
        public readonly string $purpose,
        public readonly string $recipientName,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->purpose === 'account'
                ? 'Verify Your Account Email'
                : 'Verify Your Company Email',
        );
    }

    public function content(): Content
    {
        // Blade view: resources/views/emails/registration-otp.blade.php
        return new Content(view: 'emails.registration-otp');
    }

    public function attachments(): array
    {
        return [];
    }
}
