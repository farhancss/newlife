<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Generic transactional email used by the unified notification pipeline. Every
 * student-facing notification can render through this single branded template.
 */
class PortalNotificationMail extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public string $subjectLine,
        public string $heading,
        public string $bodyText,
        public ?string $actionUrl = null,
        public ?string $actionLabel = null,
        public ?string $greetingName = null,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->subjectLine,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.portal-notification',
            with: [
                'heading' => $this->heading,
                'bodyText' => $this->bodyText,
                'actionUrl' => $this->actionUrl,
                'actionLabel' => $this->actionLabel ?? 'View in portal',
                'greetingName' => $this->greetingName,
            ],
        );
    }
}
