<?php

namespace App\Services\Squarespace;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class SquarespaceApiClient
{
    public function __construct(
        private readonly SquarespaceOAuthService $oauth,
        private readonly SquarespaceLogger $logger,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function getOrder(string $orderId): array
    {
        return $this->get('/commerce/orders/' . $orderId, 'api.getOrder');
    }

    /**
     * @return array<string, mixed>
     */
    public function getContact(string $contactId): array
    {
        return $this->get('/commerce/contacts/' . $contactId, 'api.getContact');
    }

    /**
     * Shared, OAuth-authenticated, logged GET helper.
     *
     * @return array<string, mixed>
     */
    private function get(string $path, string $label): array
    {
        $url = $this->baseUrl() . $path;
        $start = microtime(true);

        try {
            $response = $this->request()->get($url);
        } catch (\Throwable $e) {
            $this->logger->logOutgoing($label, 'GET', $url, [], null, $this->ms($start), $e->getMessage());
            throw new RuntimeException('Squarespace API request failed: ' . $e->getMessage(), previous: $e);
        }

        $this->logger->logOutgoing($label, 'GET', $url, [], $response, $this->ms($start));

        if (! $response->successful()) {
            throw new RuntimeException(
                'Squarespace API request failed: ' . $response->status() . ' ' . $response->body()
            );
        }

        return $response->json() ?? [];
    }

    /**
     * A pending request pre-authenticated with the mandatory User-Agent header
     * and a bearer token: the OAuth access token when connected, otherwise the
     * configured private API key.
     */
    public function request(): PendingRequest
    {
        return Http::withToken($this->authToken())
            ->withHeaders(['User-Agent' => (string) config('squarespace.user_agent')])
            ->acceptJson();
    }

    /**
     * Prefer the OAuth access token (required for webhook subscriptions and the
     * recommended auth for all calls); fall back to the private API key for
     * read endpoints when OAuth has not been connected yet.
     */
    private function authToken(): string
    {
        if ($this->oauth->isConnected()) {
            return $this->oauth->validAccessToken();
        }

        $apiKey = config('squarespace.api_key');

        if (is_string($apiKey) && $apiKey !== '') {
            return $apiKey;
        }

        throw new RuntimeException('No Squarespace credentials available. Connect over OAuth or set SQUARESPACE_API_KEY.');
    }

    public function baseUrl(): string
    {
        return rtrim((string) config('squarespace.api_base_url'), '/');
    }

    private function ms(float $start): int
    {
        return (int) round((microtime(true) - $start) * 1000);
    }
}
