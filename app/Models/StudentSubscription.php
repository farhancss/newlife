<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentSubscription extends Model
{
    protected $fillable = [
        'student_profile_id',
        'squarespace_order_id',
        'status',
        'sku',
        'product_name',
        'billing_period',
        'current_period_ends_at',
        'raw_payload',
        'synced_at',
    ];

    protected function casts(): array
    {
        return [
            'raw_payload' => 'array',
            'current_period_ends_at' => 'datetime',
            'synced_at' => 'datetime',
        ];
    }

    public function studentProfile(): BelongsTo
    {
        return $this->belongsTo(StudentProfile::class);
    }
}
