<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OnboardingCompleteMail extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public User $user,
    ) {
    }

    public function envelope(): Envelope
    {
        $brand = config('brand.name', 'New Life Campus');

        return new Envelope(
            subject: "You're all set with {$brand} — your dashboard is ready",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.onboarding-complete',
            with: [
                'user' => $this->user,
                'dashboardUrl' => route('student.dashboard'),
            ],
        );
    }
}
