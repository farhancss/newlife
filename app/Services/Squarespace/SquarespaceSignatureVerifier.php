<?php

namespace App\Services\Squarespace;

use App\Models\SquarespaceWebhookSubscription;

class SquarespaceSignatureVerifier
{
    /**
     * Verify the `Squarespace-Signature` header against the HMAC-SHA256 of the
     * raw payload. We try every known signing secret — the per-subscription
     * secrets returned by the API first, then the configured fallback — and
     * accept either hex or base64 encodings to stay compatible across
     * Squarespace's formats.
     */
    public function verify(string $payload, ?string $signature): bool
    {
        if (config('squarespace.skip_signature_verification')) {
            return true;
        }

        if (! $signature) {
            return false;
        }

        foreach ($this->candidateSecrets() as $secret) {
            if ($secret === '' || $secret === null) {
                continue;
            }

            $rawHmac = hash_hmac('sha256', $payload, $secret, true);

            // Squarespace sends the signature as uppercase hex; compare hex
            // case-insensitively. Also accept base64 for forward compatibility.
            $hex = bin2hex($rawHmac);
            $base64 = base64_encode($rawHmac);

            if (
                hash_equals($hex, strtolower($signature))
                || hash_equals($base64, $signature)
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Stored subscription secrets first, then the configured fallback secret.
     *
     * @return list<string>
     */
    private function candidateSecrets(): array
    {
        $secrets = [];

        foreach (SquarespaceWebhookSubscription::query()->whereNotNull('secret')->get() as $subscription) {
            if (is_string($subscription->secret) && $subscription->secret !== '') {
                $secrets[] = $subscription->secret;
            }
        }

        $fallback = config('squarespace.webhook_secret');
        if (is_string($fallback) && $fallback !== '') {
            $secrets[] = $fallback;
        }

        return array_values(array_unique($secrets));
    }
}
