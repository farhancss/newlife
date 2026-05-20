<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string|null $name
 * @property string|null $email
 * @property string|null $phone
 * @property string|null $relationship
 */
class ParentGuardian extends Model
{
    protected $fillable = [
        'student_profile_id',
        'name',
        'email',
        'phone',
        'relationship',
    ];

    public function studentProfile(): BelongsTo
    {
        return $this->belongsTo(StudentProfile::class);
    }
}
