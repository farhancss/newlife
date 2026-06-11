<?php

namespace App\Services;

final class FedExLinkService
{
    public function trackingUrl(?string $trackingNumber): ?string
    {
        $number = trim((string) $trackingNumber);

        if ($number === '') {
            return null;
        }

        return 'https://www.fedex.com/fedextrack/?trknbr=' . rawurlencode($number);
    }
}
