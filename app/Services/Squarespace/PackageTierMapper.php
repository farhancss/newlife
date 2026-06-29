<?php

namespace App\Services\Squarespace;

use App\Enums\PackageTier;
use App\Models\Package;

class PackageTierMapper
{
    /**
     * Resolve the package tier a Squarespace order maps to. Resolution order:
     *   1. Explicit SKU/product-id map in config (squarespace.sku_tier_map).
     *   2. The product name against the real packages table (exact or partial,
     *      e.g. "Summit", "Summit Package", "Summit Move 2026" → summit).
     *   3. Storefront keyword fallbacks (basic → essential, standard → summit,
     *      premium → legacy).
     *
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
            $name = $this->productName($item);

            if ($name === '') {
                continue;
            }

            $slug = $this->matchPackageSlug($name) ?? $this->keywordFallback($name);

            if ($slug !== null) {
                return $slug;
            }
        }

        return PackageTier::UNKNOWN;
    }

    /**
     * Match a product name against the packages table by slug, short label, or
     * the leading word of the package name ("Summit Package" → "summit").
     */
    private function matchPackageSlug(string $productName): ?string
    {
        $name = strtolower($productName);

        /** @var \Illuminate\Support\Collection<int, Package> $packages */
        $packages = Package::query()->get(['slug', 'name']);

        foreach ($packages as $package) {
            $slug = strtolower((string) $package->slug);
            $firstWord = strtolower((string) (explode(' ', trim((string) $package->name))[0] ?? ''));

            if ($slug !== '' && str_contains($name, $slug)) {
                return $slug;
            }

            if ($firstWord !== '' && str_contains($name, $firstWord)) {
                return $slug;
            }
        }

        return null;
    }

    private function keywordFallback(string $productName): ?string
    {
        $name = strtolower($productName);

        return match (true) {
            str_contains($name, 'legacy'), str_contains($name, 'premium') => PackageTier::LEGACY,
            str_contains($name, 'summit'), str_contains($name, 'standard') => PackageTier::SUMMIT,
            str_contains($name, 'essential'), str_contains($name, 'basic') => PackageTier::ESSENTIAL,
            default => null,
        };
    }

    /**
     * @param array<string, mixed> $item
     */
    private function productName(array $item): string
    {
        return strtolower(trim((string) ($item['productName'] ?? $item['name'] ?? '')));
    }
}
