<?php

namespace App\Services;

use Illuminate\Support\Str;

/**
 * Builds external carrier tracking URLs from a retailer name and tracking
 * number. No carrier API is used in the MVP — these are deep links to public
 * tracking pages. Unknown retailers fall back to a generic tracking search.
 */
final class CarrierLinkBuilder
{
    public function build(?string $retailer, ?string $trackingNumber): ?string
    {
        $number = trim((string) $trackingNumber);

        if ($number === '') {
            return null;
        }

        $encoded = rawurlencode($number);
        $key = Str::of((string) $retailer)->lower()->trim()->value();

        return match (true) {
            str_contains($key, 'fedex') => "https://www.fedex.com/fedextrack/?trknbr={$encoded}",
            str_contains($key, 'ups') => "https://www.ups.com/track?tracknum={$encoded}",
            str_contains($key, 'usps') => "https://tools.usps.com/go/TrackConfirmAction?tLabels={$encoded}",
            str_contains($key, 'dhl') => "https://www.dhl.com/us-en/home/tracking.html?tracking-id={$encoded}",
            str_contains($key, 'amazon') => "https://www.amazon.com/progress-tracker/package/?trackingId={$encoded}",
            default => "https://www.google.com/search?q=" . rawurlencode('track package ' . $number),
        };
    }
}
