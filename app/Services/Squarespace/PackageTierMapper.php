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
                return PackageTier::normalize($map[$sku]);
            }
        }

        foreach ($lineItems as $item) {
            $name = strtolower((string) ($item['productName'] ?? $item['name'] ?? ''));

            if (str_contains($name, 'legacy')) {
                return PackageTier::LEGACY;
            }
            if (str_contains($name, 'summit')) {
                return PackageTier::SUMMIT;
            }
            if (str_contains($name, 'essential')) {
                return PackageTier::ESSENTIAL;
            }
            if (str_contains($name, 'premium')) {
                return PackageTier::LEGACY;
            }
            if (str_contains($name, 'standard')) {
                return PackageTier::SUMMIT;
            }
            if (str_contains($name, 'basic')) {
                return PackageTier::ESSENTIAL;
            }
        }

        return PackageTier::UNKNOWN;
    }
}
