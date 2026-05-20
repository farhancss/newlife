<?php

namespace App\Services\Squarespace;

use App\Enums\WebhookEventStatus;
use App\Jobs\Squarespace\ProcessSquarespaceAddressWebhook;
use App\Jobs\Squarespace\ProcessSquarespaceContactWebhook;
use App\Jobs\Squarespace\ProcessSquarespaceOrderWebhook;
use App\Models\SquarespaceWebhookEvent;
use InvalidArgumentException;

class SquarespaceWebhookDispatcher
{
    public function dispatch(array $notification): SquarespaceWebhookEvent
    {
        $notificationId = (string) ($notification['id'] ?? '');
        $topic = (string) ($notification['topic'] ?? '');

        if ($notificationId === '' || $topic === '') {
            throw new InvalidArgumentException('Webhook notification must include id and topic.');
        }

        $existing = SquarespaceWebhookEvent::query()
            ->where('notification_id', $notificationId)
            ->first();

        if ($existing && $existing->status === WebhookEventStatus::PROCESSED) {
            return $existing;
        }

        $event = $existing ?? SquarespaceWebhookEvent::query()->create([
            'notification_id' => $notificationId,
            'topic' => $topic,
            'website_id' => $notification['websiteId'] ?? null,
            'payload' => $notification,
            'status' => WebhookEventStatus::PENDING,
        ]);

        $job = match (true) {
            str_starts_with($topic, 'contact.') => new ProcessSquarespaceContactWebhook($event->id),
            str_starts_with($topic, 'address.') => new ProcessSquarespaceAddressWebhook($event->id),
            str_starts_with($topic, 'order.') => new ProcessSquarespaceOrderWebhook($event->id),
            default => null,
        };

        if ($job === null) {
            $event->update([
                'status' => WebhookEventStatus::FAILED,
                'error' => 'Unsupported webhook topic: ' . $topic,
                'processed_at' => now(),
            ]);

            return $event;
        }

        dispatch($job);

        return $event;
    }
}
