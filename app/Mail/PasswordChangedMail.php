<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PasswordChangedMail extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public User $user,
        public bool $wasFirstReset = false,
    ) {
    }

    public function envelope(): Envelope
    {
        $brand = config('brand.name', 'New Life Campus');

        $subject = $this->wasFirstReset
            ? "{$brand} — your password is set"
            : "{$brand} — your password was updated";

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.password-changed',
            with: [
                'user' => $this->user,
                'wasFirstReset' => $this->wasFirstReset,
                'loginUrl' => route('login'),
            ],
        );
    }
}
