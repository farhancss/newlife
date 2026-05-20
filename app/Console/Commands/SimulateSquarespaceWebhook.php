<?php

namespace App\Console\Commands;

use App\Services\Squarespace\SquarespaceWebhookDispatcher;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class SimulateSquarespaceWebhook extends Command
{
    protected $signature = 'squarespace:simulate
        {topic : Webhook topic e.g. contact.create}
        {--email= : Email for contact simulation}
        {--order-id= : Order id for order simulation}
        {--sync : Process the queued job + email synchronously (skip queue worker requirement)}';

    protected $description = 'Simulate a Squarespace webhook notification locally';

    public function handle(SquarespaceWebhookDispatcher $dispatcher): int
    {
        $previousQueueConnection = null;

        if ($this->option('sync')) {
            $previousQueueConnection = config('queue.default');
            config(['queue.default' => 'sync']);
        }


        $topic = $this->argument('topic');
        $email = $this->option('email') ?? 'sim-student@example.com';

        $data = match (true) {
            str_starts_with($topic, 'contact.') => [
                'contact' => [
                    'contactId' => 'sim-contact-' . Str::random(8),
                    'firstName' => 'Sim',
                    'lastName' => 'Student',
                    'primaryEmail' => ['value' => $email],
                ],
            ],
            str_starts_with($topic, 'order.') => [
                'orderId' => $this->option('order-id') ?? 'sim-order-' . Str::random(8),
                'order' => [
                    'id' => $this->option('order-id') ?? 'sim-order-' . Str::random(8),
                    'customerId' => 'sim-contact-' . Str::random(8),
                    'customerEmail' => $email,
                    'fulfillmentStatus' => 'PENDING',
                    'lineItems' => [
                        ['sku' => 'SQSP-STANDARD', 'productName' => 'Standard Move Package'],
                    ],
                    'shippingAddress' => [
                        'firstName' => 'Sim',
                        'lastName' => 'Student',
                        'line1' => '100 Main St',
                        'city' => 'Norfolk',
                        'region' => 'VA',
                        'postalCode' => '23510',
                        'countryCode' => 'US',
                    ],
                ],
            ],
            default => [],
        };

        $notification = [
            'id' => 'cli-sim-' . uniqid(),
            'websiteId' => config('squarespace.website_id', 'sim'),
            'subscriptionId' => 'cli-sim',
            'topic' => $topic,
            'createdOn' => now()->toIso8601String(),
            'data' => $data,
        ];

        try {
            $event = $dispatcher->dispatch($notification);
        } finally {
            if ($previousQueueConnection !== null) {
                config(['queue.default' => $previousQueueConnection]);
            }
        }

        $this->info("Dispatched {$event->topic} (notification {$event->notification_id}, status {$event->status})");

        if ($this->option('sync')) {
            $this->line('Processed synchronously — no queue worker required.');
        } else {
            $this->line('Queued — run `php artisan queue:work` to process the webhook and send mail.');
        }

        return self::SUCCESS;
    }
}
