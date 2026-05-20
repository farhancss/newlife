<?php

namespace App\Services\Squarespace;

use App\Enums\PackageTier;

class PackageTierMapper
{
    /**
     * @param array<int, array<string, mixed>> $lineItems
     */
    public function mapFromLineItems(array $lineItems): string
    {
        $map = config('squarespace.sku_tier_map', []);

        foreach ($lineItems as $item) {
            $sku = $item['sku'] ?? $item['productId'] ?? null;
            if ($sku && isset($map[$sku])) {
                return $map[$sku];
            }
        }

        foreach ($lineItems as $item) {
            $name = strtolower((string) ($item['productName'] ?? $item['name'] ?? ''));
            if (str_contains($name, 'premium')) {
                return PackageTier::PREMIUM;
            }
            if (str_contains($name, 'standard')) {
                return PackageTier::STANDARD;
            }
            if (str_contains($name, 'basic')) {
                return PackageTier::BASIC;
            }
        }

        return PackageTier::UNKNOWN;
    }
}
