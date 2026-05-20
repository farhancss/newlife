<?php

namespace App\Services\Squarespace;

class SquarespaceSignatureVerifier
{
    public function verify(string $payload, ?string $signature): bool
    {
        if (config('squarespace.skip_signature_verification')) {
            return true;
        }

        $secret = config('squarespace.webhook_secret');

        if (!$secret || !$signature) {
            return false;
        }

        $expected = hash_hmac('sha256', $payload, $secret);

        return hash_equals($expected, $signature);
    }
}
