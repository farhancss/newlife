<?php

namespace App\Http\Requests\Concerns;

use App\Services\TrackingNumberExtractor;

/**
 * Shared helper for retail-package form requests: when a tracking URL is
 * supplied but the tracking number is left blank, derive the number from the
 * URL so the server stays authoritative even without client-side JS.
 */
trait ResolvesTrackingNumber
{
    protected function resolveTrackingNumber(): void
    {
        $url = trim((string) $this->input('tracking_url', ''));
        $number = trim((string) $this->input('tracking_number', ''));

        if ($url === '') {
            return;
        }

        if ($number !== '') {
            return;
        }

        $extracted = app(TrackingNumberExtractor::class)->extract($url);

        if ($extracted !== null && $extracted !== '') {
            $this->merge(['tracking_number' => $extracted]);
        }
    }
}
