<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $student_profile_id
 * @property string|null $type
 * @property string|null $line1
 * @property string|null $line2
 * @property string|null $city
 * @property string|null $region
 * @property string|null $postal_code
 * @property string|null $country_code
 * @property string|null $phone
 * @property string|null $shipping_notes
 * @property \Illuminate\Support\Carbon|null $confirmed_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class ShippingAddress extends Model
{
    protected $fillable = [
        'student_profile_id',
        'type',
        'line1',
        'line2',
        'city',
        'region',
        'postal_code',
        'country_code',
        'phone',
        'shipping_notes',
        'confirmed_at',
    ];

    protected function casts(): array
    {
        return [
            'confirmed_at' => 'datetime',
        ];
    }

    public function studentProfile(): BelongsTo
    {
        return $this->belongsTo(StudentProfile::class);
    }
}
