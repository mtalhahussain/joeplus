<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class UserRegistrationOtp extends Mailable
{
    use Queueable, SerializesModels;

    protected int $otp;
    protected string $logo;
    protected string $expire;

    public function __construct($otp, $logo, $expire)
    {
        $this->otp = $otp;
        $this->logo = $logo;
        $this->expire = $expire;
    }
    
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'OTP Verification',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.register_mail',
            with: [
                'otp' => $this->otp,
                'logo' => $this->logo,
                'expire' => $this->expire,
            ]
        );
    }

    public function attachments(): array
    {
        return [];
    }
}