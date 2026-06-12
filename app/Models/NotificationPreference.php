<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property bool $email_enabled
 * @property bool $sms_enabled
 * @property string|null $sms_number
 * @property bool $parent_cc_enabled
 * @property-read User $user
 */
class NotificationPreference extends Model
{
    protected $fillable = [
        'user_id',
        'email_enabled',
        'sms_enabled',
        'sms_number',
        'parent_cc_enabled',
    ];

    protected function casts(): array
    {
        return [
            'email_enabled' => 'boolean',
            'sms_enabled' => 'boolean',
            'parent_cc_enabled' => 'boolean',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
