<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $student_profile_id
 * @property string|null $university
 * @property string|null $residence_hall
 * @property string|null $building
 * @property string|null $room
 * @property \Illuminate\Support\Carbon|null $move_in_date
 * @property string|null $move_in_window
 * @property string|null $delivery_notes
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class HousingInfo extends Model
{
    protected $fillable = [
        'student_profile_id',
        'university',
        'residence_hall',
        'building',
        'room',
        'move_in_date',
        'move_in_window',
        'delivery_notes',
    ];

    protected function casts(): array
    {
        return [
            'move_in_date' => 'date',
        ];
    }

    public function studentProfile(): BelongsTo
    {
        return $this->belongsTo(StudentProfile::class);
    }
}
