<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A single product line on a {@see SquarespaceOrder}.
 *
 * @property int $id
 * @property int $squarespace_order_id
 * @property string|null $line_item_id
 * @property string|null $product_id
 * @property string|null $product_name
 * @property string|null $sku
 * @property array<string, mixed>|null $variant_options
 * @property int $quantity
 * @property int|null $unit_price_cents
 * @property int|null $total_price_cents
 * @property string|null $image_url
 * @property array<string, mixed>|null $raw
 */
class SquarespaceOrderItem extends Model
{
    protected $fillable = [
        'squarespace_order_id',
        'line_item_id',
        'product_id',
        'product_name',
        'sku',
        'variant_options',
        'quantity',
        'unit_price_cents',
        'total_price_cents',
        'image_url',
        'raw',
    ];

    protected function casts(): array
    {
        return [
            'variant_options' => 'array',
            'raw' => 'array',
            'quantity' => 'integer',
            'unit_price_cents' => 'integer',
            'total_price_cents' => 'integer',
        ];
    }

    /** @return BelongsTo<SquarespaceOrder, $this> */
    public function order(): BelongsTo
    {
        return $this->belongsTo(SquarespaceOrder::class, 'squarespace_order_id');
    }

    public function formattedTotal(): string
    {
        if ($this->total_price_cents === null) {
            return '—';
        }

        return '$' . number_format($this->total_price_cents / 100, 2);
    }
}
