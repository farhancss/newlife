<?php

namespace App\Services;

use Illuminate\Support\Str;

/**
 * Extracts a shipment tracking number from a carrier/retailer tracking URL.
 *
 * Retailers (e.g. Amazon) hand shipments to different carriers, so the URL can
 * come from many providers. We first look for well-known query parameters and
 * fall back to scanning the URL path for a token that looks like a tracking
 * number. A raw tracking number (not a URL) is returned unchanged.
 */
final class TrackingNumberExtractor
{
    /**
     * Common query-string keys carriers use to carry the tracking number.
     * Compared case-insensitively.
     *
     * @var array<int, string>
     */
    private const QUERY_KEYS = [
        'trknbr',          // FedEx
        'tracknum',        // UPS
        'tracknumbers',
        'tracking_numbers',
        'tlabels',         // USPS (tLabels)
        'qtc_tlabels1',    // USPS alt
        'tracking-id',     // DHL
        'trackingid',      // Amazon
        'trackingnumber',
        'tracking_number',
        'tracking',
        'tn',
        'id',
    ];

    public function extract(?string $input): ?string
    {
        $value = trim((string) $input);

        if ($value === '') {
            return null;
        }

        // Not a URL — treat the input as a raw tracking number.
        if (! Str::contains($value, ['://', '?', '/'])) {
            return $this->clean($value);
        }

        $parts = parse_url($value);

        if ($parts === false) {
            return $this->clean($value);
        }

        if (! empty($parts['query'])) {
            $fromQuery = $this->fromQuery($parts['query']);

            if ($fromQuery !== null) {
                return $fromQuery;
            }
        }

        if (! empty($parts['path'])) {
            $fromPath = $this->fromPath($parts['path']);

            if ($fromPath !== null) {
                return $fromPath;
            }
        }

        return null;
    }

    private function fromQuery(string $query): ?string
    {
        parse_str($query, $params);

        $normalized = [];
        foreach ($params as $key => $param) {
            if (is_string($param)) {
                $normalized[strtolower((string) $key)] = $param;
            }
        }

        foreach (self::QUERY_KEYS as $key) {
            if (isset($normalized[$key]) && trim($normalized[$key]) !== '') {
                return $this->clean($normalized[$key]);
            }
        }

        return null;
    }

    private function fromPath(string $path): ?string
    {
        $segments = array_values(array_filter(explode('/', $path), fn ($segment) => $segment !== ''));

        // Prefer the last meaningful segment (carriers usually end the URL with it).
        foreach (array_reverse($segments) as $segment) {
            $segment = rawurldecode($segment);

            if ($this->looksLikeTrackingNumber($segment)) {
                return $this->clean($segment);
            }
        }

        return null;
    }

    private function looksLikeTrackingNumber(string $candidate): bool
    {
        return (bool) preg_match('/^[A-Za-z0-9]{8,40}$/', $candidate)
            && (bool) preg_match('/\d/', $candidate);
    }

    private function clean(string $value): string
    {
        // Carriers sometimes pass multiple comma/space separated numbers.
        $first = preg_split('/[,\s]+/', trim($value))[0] ?? trim($value);

        return Str::limit($first, 64, '');
    }
}
