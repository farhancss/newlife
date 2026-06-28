<?php

namespace App\Services\Squarespace;

use App\Models\SquarespaceOrder;
use App\Models\StudentProfile;
use App\Services\AddOnService;
use Illuminate\Support\Carbon;

/**
 * Persists the full purchase detail of a Squarespace order — header totals plus
 * every product line item — and activates any add-ons whose SKU is mapped in
 * config. Keeps the customer's complete purchase history in the portal.
 */
class SquarespaceOrderImporter
{
    public function __construct(
        private readonly AddOnService $addOns,
    ) {
    }

    /**
     * @param  array<string, mixed>  $order
     */
    public function import(array $order, ?StudentProfile $profile): SquarespaceOrder
    {
        $orderId = (string) ($order['id'] ?? $order['orderId'] ?? '');
        $lineItems = is_array($order['lineItems'] ?? null) ? $order['lineItems'] : [];

        $record = SquarespaceOrder::query()->updateOrCreate(
            ['squarespace_order_id' => $orderId],
            [
                'student_profile_id' => $profile?->id,
                'order_number' => $order['orderNumber'] ?? null,
                'customer_id' => $order['customerId'] ?? null,
                'customer_email' => $order['customerEmail'] ?? ($order['billingAddress']['email'] ?? null),
                'fulfillment_status' => $order['fulfillmentStatus'] ?? null,
                'channel' => $order['channel'] ?? ($order['channelName'] ?? null),
                'currency' => $this->currency($order),
                'subtotal_cents' => $this->cents($order['subtotal'] ?? null),
                'shipping_total_cents' => $this->cents($order['shippingTotal'] ?? null),
                'tax_total_cents' => $this->cents($order['taxTotal'] ?? null),
                'grand_total_cents' => $this->cents($order['grandTotal'] ?? null),
                'raw_payload' => $order,
                'placed_at' => $this->parseDate($order['createdOn'] ?? null),
                'synced_at' => Carbon::now(),
            ]
        );

        // Replace line items so re-imported orders stay in sync.
        $record->items()->delete();

        foreach ($lineItems as $item) {
            if (! is_array($item)) {
                continue;
            }

            $quantity = (int) ($item['quantity'] ?? 1);
            $unitCents = $this->cents($item['unitPricePaid'] ?? $item['unitPrice'] ?? null);

            $record->items()->create([
                'line_item_id' => $item['id'] ?? null,
                'product_id' => $item['productId'] ?? null,
                'product_name' => $item['productName'] ?? $item['name'] ?? null,
                'sku' => $item['sku'] ?? null,
                'variant_options' => $item['variantOptions'] ?? null,
                'quantity' => max(1, $quantity),
                'unit_price_cents' => $unitCents,
                'total_price_cents' => $this->cents($item['lineTotal'] ?? null)
                    ?? ($unitCents !== null ? $unitCents * max(1, $quantity) : null),
                'image_url' => $item['imageUrl'] ?? null,
                'raw' => $item,
            ]);
        }

        if ($profile !== null && $orderId !== '') {
            $this->activateAddOns($profile, $lineItems, $orderId);
        }

        return $record->fresh(['items']) ?? $record;
    }

    /**
     * Activate add-ons for any line item whose SKU is mapped in
     * config('squarespace.addon_sku_map'). Idempotent per order + slug.
     *
     * @param  array<int, mixed>  $lineItems
     */
    private function activateAddOns(StudentProfile $profile, array $lineItems, string $orderId): void
    {
        /** @var array<string, string> $map */
        $map = config('squarespace.addon_sku_map', []);

        if ($map === []) {
            return;
        }

        foreach ($lineItems as $item) {
            if (! is_array($item)) {
                continue;
            }

            $sku = (string) ($item['sku'] ?? '');
            $slug = $map[$sku] ?? null;

            if ($slug === null) {
                continue;
            }

            $catalogEntry = $this->addOns->findInCatalog($slug);

            if ($catalogEntry === null) {
                continue;
            }

            $this->addOns->activateFromSquarespace(
                $profile,
                $catalogEntry,
                $orderId,
                max(1, (int) ($item['quantity'] ?? 1)),
            );
        }
    }

    /**
     * @param  array<string, mixed>  $order
     */
    private function currency(array $order): ?string
    {
        return $order['grandTotal']['currency']
            ?? $order['subtotal']['currency']
            ?? null;
    }

    /**
     * Convert a Squarespace money object ({ value, currency }) or numeric string
     * into integer cents.
     */
    private function cents(mixed $money): ?int
    {
        if ($money === null) {
            return null;
        }

        $value = is_array($money) ? ($money['value'] ?? null) : $money;

        if ($value === null || $value === '') {
            return null;
        }

        return (int) round(((float) $value) * 100);
    }

    private function parseDate(?string $value): ?Carbon
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }
}
