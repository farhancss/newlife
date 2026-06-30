<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class StudentInvitationMail extends Mailable
{
    use SerializesModels;

    public function __construct(
        public User $user,
        public string $temporaryPassword,
    ) {
    }

    public function envelope(): Envelope
    {
        $brand = config('brand.name', 'New Life Campus');

        return new Envelope(
            subject: "Welcome to {$brand} — your portal account is ready",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.student-invitation',
            with: [
                'user' => $this->user,
                'temporaryPassword' => $this->temporaryPassword,
                'loginUrl' => route('login'),
            ],
        );
    }
}
