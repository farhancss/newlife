<?php

namespace App\Jobs\Squarespace;

use App\Enums\WebhookEventStatus;
use App\Models\SquarespaceWebhookEvent;
use App\Services\AccountProvisioningService;
use App\Services\Squarespace\SquarespaceApiClient;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class ProcessSquarespaceOrderWebhook implements ShouldQueue, ShouldBeUnique
{
    use Queueable;

    public int $uniqueFor = 3600;

    public function __construct(public int $webhookEventId)
    {
    }

    public function uniqueId(): string
    {
        return 'squarespace-order-' . $this->webhookEventId;
    }

    public function handle(
        AccountProvisioningService $provisioning,
        SquarespaceApiClient $apiClient,
    ): void {
        $event = SquarespaceWebhookEvent::query()->findOrFail($this->webhookEventId);
        $event->update(['status' => WebhookEventStatus::PROCESSING]);

        try {
            $data = $event->payload['data'] ?? [];
            $orderId = (string) ($data['orderId'] ?? '');
            $embeddedOrder = $data['order'] ?? null;

            if (is_array($embeddedOrder) && $embeddedOrder !== []) {
                $order = $embeddedOrder;
            } elseif ($orderId !== '') {
                $order = $apiClient->getOrder($orderId);
            } else {
                $order = [];
            }

            if ($order === []) {
                throw new \RuntimeException('Order webhook missing orderId and order payload.');
            }

            $provisioning->enrichFromOrder($order);
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
