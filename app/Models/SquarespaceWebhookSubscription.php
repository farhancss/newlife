<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A webhook subscription registered with Squarespace. The signing secret is
 * stored encrypted and used to verify inbound notification signatures.
 *
 * @property int $id
 * @property string $subscription_id
 * @property string $endpoint_url
 * @property array<int, string> $topics
 * @property string|null $secret
 * @property string|null $website_id
 * @property string|null $client_id
 * @property \Illuminate\Support\Carbon|null $remote_created_on
 * @property \Illuminate\Support\Carbon|null $remote_updated_on
 * @property string|null $last_test_status
 * @property \Illuminate\Support\Carbon|null $last_test_at
 */
class SquarespaceWebhookSubscription extends Model
{
    protected $fillable = [
        'subscription_id',
        'endpoint_url',
        'topics',
        'secret',
        'website_id',
        'client_id',
        'remote_created_on',
        'remote_updated_on',
        'last_test_status',
        'last_test_at',
    ];

    protected $hidden = [
        'secret',
    ];

    protected function casts(): array
    {
        return [
            'topics' => 'array',
            'secret' => 'encrypted',
            'remote_created_on' => 'datetime',
            'remote_updated_on' => 'datetime',
            'last_test_at' => 'datetime',
        ];
    }
}
