<?php

namespace App\Services\Squarespace;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class SquarespaceApiClient
{
    public function getOrder(string $orderId): array
    {
        $response = Http::withToken($this->accessToken())
            ->acceptJson()
            ->get($this->baseUrl() . '/commerce/orders/' . $orderId);

        if (!$response->successful()) {
            throw new RuntimeException(
                'Failed to fetch Squarespace order: ' . $response->status() . ' ' . $response->body()
            );
        }

        return $response->json() ?? [];
    }

    public function getContact(string $contactId): array
    {
        $response = Http::withToken($this->accessToken())
            ->acceptJson()
            ->get($this->baseUrl() . '/commerce/contacts/' . $contactId);

        if (!$response->successful()) {
            throw new RuntimeException(
                'Failed to fetch Squarespace contact: ' . $response->status() . ' ' . $response->body()
            );
        }

        return $response->json() ?? [];
    }

    private function accessToken(): string
    {
        return Cache::remember('squarespace_access_token', 3500, function (): string {
            $clientId = config('squarespace.client_id');
            $clientSecret = config('squarespace.client_secret');

            if (!$clientId || !$clientSecret) {
                throw new RuntimeException('Squarespace API credentials are not configured.');
            }

            $response = Http::asForm()
                ->post($this->baseUrl() . '/oauth/token', [
                    'grant_type' => 'client_credentials',
                    'client_id' => $clientId,
                    'client_secret' => $clientSecret,
                ]);

            if (!$response->successful()) {
                throw new RuntimeException(
                    'Failed to obtain Squarespace access token: ' . $response->status()
                );
            }

            $token = $response->json('access_token');

            if (!is_string($token) || $token === '') {
                throw new RuntimeException('Squarespace access token missing from response.');
            }

            return $token;
        });
    }

    private function baseUrl(): string
    {
        return rtrim((string) config('squarespace.api_base_url'), '/');
    }
}
