<?php

namespace App\Jobs\Squarespace;

use App\Enums\WebhookEventStatus;
use App\Models\SquarespaceWebhookEvent;
use App\Services\AccountProvisioningService;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class ProcessSquarespaceContactWebhook implements ShouldQueue, ShouldBeUnique
{
    use Queueable;

    public int $uniqueFor = 3600;

    public function __construct(public int $webhookEventId)
    {
    }

    public function uniqueId(): string
    {
        return 'squarespace-contact-' . $this->webhookEventId;
    }

    public function handle(AccountProvisioningService $provisioning): void
    {
        $event = SquarespaceWebhookEvent::query()->findOrFail($this->webhookEventId);
        $event->update(['status' => WebhookEventStatus::PROCESSING]);

        try {
            $provisioning->upsertFromContactNotification($event->payload);
            $event->update([
                'status' => WebhookEventStatus::PROCESSED,
                'processed_at' => now(),
                'error' => null,
            ]);
        } catch (Throwable $e) {
            $event->update([
                'status' => WebhookEventStatus::FAILED,
                'processed_at' => now(),
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
