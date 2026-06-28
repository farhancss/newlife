<?php

namespace App\Services\Squarespace;

use App\Models\SquarespaceWebhookSubscription;
use Illuminate\Support\Carbon;
use RuntimeException;

/**
 * Manages Squarespace webhook subscriptions via the WebhookSubscriptions API.
 * Creating a subscription returns a signing secret that we store (encrypted)
 * and use to verify inbound notification signatures.
 *
 * @see https://developers.squarespace.com/commerce-apis/webhooksubscriptions
 */
class SquarespaceWebhookSubscriptionService
{
    public function __construct(
        private readonly SquarespaceApiClient $client,
        private readonly SquarespaceLogger $logger,
    ) {
    }

    /**
     * The endpoint URL Squarespace should POST notifications to.
     */
    public function endpointUrl(): string
    {
        return route('api.webhooks.squarespace');
    }

    /**
     * @return list<string>
     */
    public function defaultTopics(): array
    {
        /** @var list<string> $topics */
        $topics = config('squarespace.webhook_topics', []);

        return $topics;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function list(): array
    {
        $data = $this->send('GET', '/webhook_subscriptions', [], 'subscription.list');

        /** @var array<int, array<string, mixed>> $subs */
        $subs = $data['webhookSubscriptions'] ?? [];

        return $subs;
    }

    /**
     * Create a subscription and persist it (with its signing secret) locally.
     *
     * @param  list<string>|null  $topics
     */
    public function create(?array $topics = null): SquarespaceWebhookSubscription
    {
        $topics ??= $this->defaultTopics();
        $endpoint = $this->endpointUrl();

        $data = $this->send('POST', '/webhook_subscriptions', [
            'endpointUrl' => $endpoint,
            'topics' => array_values($topics),
        ], 'subscription.create');

        $subscriptionId = (string) ($data['id'] ?? '');

        if ($subscriptionId === '') {
            throw new RuntimeException('Squarespace did not return a subscription id.');
        }

        return SquarespaceWebhookSubscription::query()->updateOrCreate(
            ['subscription_id' => $subscriptionId],
            [
                'endpoint_url' => $data['endpointUrl'] ?? $endpoint,
                'topics' => $data['topics'] ?? $topics,
                'secret' => $data['secret'] ?? null,
                'website_id' => $data['websiteId'] ?? config('squarespace.website_id'),
                'client_id' => $data['clientId'] ?? null,
                'remote_created_on' => $this->parseDate($data['createdOn'] ?? null),
                'remote_updated_on' => $this->parseDate($data['updatedOn'] ?? null),
            ]
        );
    }

    public function delete(string $subscriptionId): void
    {
        $this->send('DELETE', '/webhook_subscriptions/' . $subscriptionId, [], 'subscription.delete');

        SquarespaceWebhookSubscription::query()
            ->where('subscription_id', $subscriptionId)
            ->delete();
    }

    public function rotateSecret(string $subscriptionId): SquarespaceWebhookSubscription
    {
        $data = $this->send(
            'POST',
            '/webhook_subscriptions/' . $subscriptionId . '/actions/rotateSecret',
            [],
            'subscription.rotateSecret',
        );

        $subscription = SquarespaceWebhookSubscription::query()
            ->where('subscription_id', $subscriptionId)
            ->firstOrFail();

        if (! empty($data['secret'])) {
            $subscription->secret = $data['secret'];
            $subscription->save();
        }

        return $subscription;
    }

    public function sendTest(string $subscriptionId, string $topic): int
    {
        $data = $this->send(
            'POST',
            '/webhook_subscriptions/' . $subscriptionId . '/actions/sendTestNotification',
            ['topic' => $topic],
            'subscription.sendTest',
        );

        $statusCode = (int) ($data['statusCode'] ?? 0);

        SquarespaceWebhookSubscription::query()
            ->where('subscription_id', $subscriptionId)
            ->update([
                'last_test_status' => (string) $statusCode,
                'last_test_at' => Carbon::now(),
            ]);

        return $statusCode;
    }

    /**
     * Issue an OAuth-authenticated, logged request to the WebhookSubscriptions
     * API.
     *
     * @param  array<string, mixed>  $body
     * @return array<string, mixed>
     */
    private function send(string $method, string $path, array $body, string $label): array
    {
        $url = $this->client->baseUrl() . $path;
        $start = microtime(true);

        try {
            $request = $this->client->request();
            $response = match (strtoupper($method)) {
                'GET' => $request->get($url),
                'DELETE' => $request->delete($url),
                default => $request->post($url, $body),
            };
        } catch (\Throwable $e) {
            $this->logger->logOutgoing($label, $method, $url, $body, null, $this->ms($start), $e->getMessage());
            throw new RuntimeException('Squarespace subscription request failed: ' . $e->getMessage(), previous: $e);
        }

        $this->logger->logOutgoing($label, $method, $url, $body, $response, $this->ms($start));

        if (! $response->successful()) {
            throw new RuntimeException(
                'Squarespace subscription request failed (' . $response->status() . '): ' . $response->body()
            );
        }

        return $response->json() ?? [];
    }

    private function parseDate(?string $value): ?Carbon
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }

    private function ms(float $start): int
    {
        return (int) round((microtime(true) - $start) * 1000);
    }
}
