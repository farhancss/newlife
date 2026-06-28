<?php

namespace App\Services\Squarespace;

use App\Models\SquarespaceCredential;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Handles the Squarespace OAuth authorization-code flow: building the consent
 * URL, exchanging the returned code for tokens, persisting them encrypted, and
 * transparently refreshing the access token before it expires.
 */
class SquarespaceOAuthService
{
    public function __construct(
        private readonly SquarespaceLogger $logger,
    ) {
    }

    public function isConfigured(): bool
    {
        return (string) config('squarespace.client_id') !== ''
            && (string) config('squarespace.client_secret') !== '';
    }

    public function current(): ?SquarespaceCredential
    {
        return SquarespaceCredential::query()->latest('id')->first();
    }

    public function isConnected(): bool
    {
        $credential = $this->current();

        return $credential !== null && $credential->refresh_token !== null;
    }

    public function redirectUri(): string
    {
        $configured = (string) config('squarespace.oauth.redirect_uri');

        return $configured !== '' ? $configured : route('squarespace.callback');
    }

    /**
     * @return list<string>
     */
    public function scopes(): array
    {
        /** @var list<string> $scopes */
        $scopes = config('squarespace.oauth.scopes', []);

        return $scopes;
    }

    /**
     * Build the Squarespace consent URL the admin is redirected to. The $state
     * value must be persisted (e.g. in session) and verified on callback.
     */
    public function authorizationUrl(string $state): string
    {
        $query = http_build_query([
            'client_id' => (string) config('squarespace.client_id'),
            'redirect_uri' => $this->redirectUri(),
            'scope' => implode(',', $this->scopes()),
            'state' => $state,
            'access_type' => 'offline',
            'response_type' => 'code',
        ]);

        return rtrim((string) config('squarespace.oauth.authorize_url'), '?') . '?' . $query;
    }

    public function generateState(): string
    {
        return Str::random(40);
    }

    /**
     * Exchange an authorization code for tokens and persist them.
     */
    public function handleCallback(string $code, ?User $actor = null): SquarespaceCredential
    {
        $payload = $this->requestToken([
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => $this->redirectUri(),
        ], 'oauth.authorization_code');

        return $this->store($payload, $actor);
    }

    /**
     * Return a valid access token, refreshing it first if it is expired or
     * about to expire.
     */
    public function validAccessToken(): string
    {
        $credential = $this->current();

        if ($credential === null) {
            throw new RuntimeException('Squarespace is not connected. Complete the OAuth connection first.');
        }

        $leeway = (int) config('squarespace.oauth.refresh_leeway', 120);

        if ($credential->isExpired($leeway)) {
            $credential = $this->refresh($credential);
        }

        return $credential->access_token;
    }

    public function refresh(SquarespaceCredential $credential): SquarespaceCredential
    {
        if ($credential->refresh_token === null) {
            throw new RuntimeException('No Squarespace refresh token available; reconnect the integration.');
        }

        $payload = $this->requestToken([
            'grant_type' => 'refresh_token',
            'refresh_token' => $credential->refresh_token,
        ], 'oauth.refresh');

        return $this->store($payload, null, $credential);
    }

    public function disconnect(): void
    {
        SquarespaceCredential::query()->delete();
    }

    /**
     * @param  array<string, mixed>  $body
     * @return array<string, mixed>
     */
    private function requestToken(array $body, string $label): array
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('Squarespace OAuth client credentials are not configured.');
        }

        $url = (string) config('squarespace.oauth.token_url');
        $start = microtime(true);

        try {
            $response = Http::withBasicAuth(
                (string) config('squarespace.client_id'),
                (string) config('squarespace.client_secret'),
            )
                ->withHeaders(['User-Agent' => (string) config('squarespace.user_agent')])
                ->acceptJson()
                ->asJson()
                ->post($url, $body);
        } catch (\Throwable $e) {
            $this->logger->logOutgoing($label, 'POST', $url, $body, null, $this->ms($start), $e->getMessage());
            throw new RuntimeException('Squarespace token request failed: ' . $e->getMessage(), previous: $e);
        }

        $this->logger->logOutgoing($label, 'POST', $url, $body, $response, $this->ms($start));

        if (! $response->successful()) {
            throw new RuntimeException(
                'Squarespace token request rejected (' . $response->status() . '): ' . $response->body()
            );
        }

        return $response->json() ?? [];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function store(array $payload, ?User $actor = null, ?SquarespaceCredential $existing = null): SquarespaceCredential
    {
        $accessToken = (string) ($payload['access_token'] ?? '');

        if ($accessToken === '') {
            throw new RuntimeException('Squarespace token response did not include an access token.');
        }

        $expiresIn = (int) ($payload['expires_in'] ?? 0);
        $refreshExpiresIn = (int) ($payload['refresh_token_expires_in'] ?? 0);

        $credential = $existing ?? $this->current() ?? new SquarespaceCredential();

        $credential->access_token = $accessToken;
        // Squarespace omits the refresh token on refresh responses sometimes;
        // keep the existing one when not returned.
        $credential->refresh_token = ($payload['refresh_token'] ?? null) ?: $credential->refresh_token;
        $credential->token_type = $payload['token_type'] ?? $credential->token_type;
        $credential->scopes = implode(',', $this->scopes());
        $credential->website_id = $payload['websiteId'] ?? config('squarespace.website_id') ?? $credential->website_id;
        $credential->expires_at = $expiresIn > 0 ? Carbon::now()->addSeconds($expiresIn) : null;
        $credential->refresh_token_expires_at = $refreshExpiresIn > 0 ? Carbon::now()->addSeconds($refreshExpiresIn) : $credential->refresh_token_expires_at;

        if ($credential->connected_at === null) {
            $credential->connected_at = Carbon::now();
        }

        if ($actor !== null) {
            $credential->connected_by_user_id = $actor->id;
        }

        $credential->last_refreshed_at = Carbon::now();
        $credential->save();

        return $credential;
    }

    private function ms(float $start): int
    {
        return (int) round((microtime(true) - $start) * 1000);
    }
}
