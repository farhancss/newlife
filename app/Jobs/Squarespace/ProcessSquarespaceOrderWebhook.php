<?php

namespace App\Jobs\Squarespace;

use App\Enums\WebhookEventStatus;
use App\Models\SquarespaceWebhookEvent;
use App\Services\AccountProvisioningService;
use App\Services\Squarespace\SquarespaceApiClient;
use App\Services\Squarespace\SquarespaceLogger;
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
        SquarespaceLogger $logger,
    ): void {
        $event = SquarespaceWebhookEvent::query()->findOrFail($this->webhookEventId);
        $event->update(['status' => WebhookEventStatus::PROCESSING]);

        $data = $event->payload['data'] ?? [];
        $orderId = (string) ($data['orderId'] ?? '');

        try {
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

            $profile = $provisioning->enrichFromOrder($order);
            $event->update([
                'status' => WebhookEventStatus::PROCESSED,
                'processed_at' => now(),
                'error' => null,
            ]);

            $logger->logProcessing('order.provisioned', 'order ' . $orderId, [
                'order_id' => $orderId,
                'order_number' => $order['orderNumber'] ?? null,
                'student_email' => $profile->user?->email,
                'package_tier' => $profile->package_tier,
                'package_price_cents' => $profile->package_price_cents,
            ]);
        } catch (Throwable $e) {
            $event->update([
                'status' => WebhookEventStatus::FAILED,
                'processed_at' => now(),
                'error' => $e->getMessage(),
            ]);

            $logger->logProcessing('order.failed', 'order ' . $orderId, [
                'order_id' => $orderId,
            ], $e->getMessage());

            throw $e;
        }
    }
}
