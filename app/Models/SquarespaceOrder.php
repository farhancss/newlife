<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A persisted Squarespace order (purchase) with its line items, captured from
 * order webhooks so the full purchase history is visible in the portal.
 *
 * @property int $id
 * @property int|null $student_profile_id
 * @property string $squarespace_order_id
 * @property string|null $order_number
 * @property string|null $customer_id
 * @property string|null $customer_email
 * @property string|null $fulfillment_status
 * @property string|null $channel
 * @property string|null $currency
 * @property int|null $subtotal_cents
 * @property int|null $shipping_total_cents
 * @property int|null $tax_total_cents
 * @property int|null $grand_total_cents
 * @property array<string, mixed>|null $raw_payload
 * @property \Illuminate\Support\Carbon|null $placed_at
 * @property \Illuminate\Support\Carbon|null $synced_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, SquarespaceOrderItem> $items
 */
class SquarespaceOrder extends Model
{
    protected $fillable = [
        'student_profile_id',
        'squarespace_order_id',
        'order_number',
        'customer_id',
        'customer_email',
        'fulfillment_status',
        'channel',
        'currency',
        'subtotal_cents',
        'shipping_total_cents',
        'tax_total_cents',
        'grand_total_cents',
        'raw_payload',
        'placed_at',
        'synced_at',
    ];

    protected function casts(): array
    {
        return [
            'raw_payload' => 'array',
            'subtotal_cents' => 'integer',
            'shipping_total_cents' => 'integer',
            'tax_total_cents' => 'integer',
            'grand_total_cents' => 'integer',
            'placed_at' => 'datetime',
            'synced_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<StudentProfile, $this> */
    public function studentProfile(): BelongsTo
    {
        return $this->belongsTo(StudentProfile::class);
    }

    /** @return HasMany<SquarespaceOrderItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(SquarespaceOrderItem::class);
    }

    public function formattedTotal(): string
    {
        if ($this->grand_total_cents === null) {
            return '—';
        }

        return ($this->currency ? $this->currency . ' ' : '$') . number_format($this->grand_total_cents / 100, 2);
    }
}
