<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * The single stored OAuth credential for the Squarespace connection. Tokens are
 * encrypted at rest. There is at most one active row.
 *
 * @property int $id
 * @property string $access_token
 * @property string|null $refresh_token
 * @property string|null $token_type
 * @property string|null $scopes
 * @property string|null $website_id
 * @property \Illuminate\Support\Carbon|null $expires_at
 * @property \Illuminate\Support\Carbon|null $refresh_token_expires_at
 * @property \Illuminate\Support\Carbon|null $connected_at
 * @property \Illuminate\Support\Carbon|null $last_refreshed_at
 * @property int|null $connected_by_user_id
 */
class SquarespaceCredential extends Model
{
    protected $fillable = [
        'access_token',
        'refresh_token',
        'token_type',
        'scopes',
        'website_id',
        'expires_at',
        'refresh_token_expires_at',
        'connected_at',
        'last_refreshed_at',
        'connected_by_user_id',
    ];

    protected $hidden = [
        'access_token',
        'refresh_token',
    ];

    protected function casts(): array
    {
        return [
            'access_token' => 'encrypted',
            'refresh_token' => 'encrypted',
            'expires_at' => 'datetime',
            'refresh_token_expires_at' => 'datetime',
            'connected_at' => 'datetime',
            'last_refreshed_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function connectedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'connected_by_user_id');
    }

    /**
     * Whether the access token is expired (or will be within $leeway seconds).
     */
    public function isExpired(int $leeway = 0): bool
    {
        if ($this->expires_at === null) {
            return true;
        }

        return $this->expires_at->lte(Carbon::now()->addSeconds($leeway));
    }
}
