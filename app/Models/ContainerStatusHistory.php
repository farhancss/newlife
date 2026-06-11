<?php

namespace App\Models;

use App\Enums\ContainerStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $container_id
 * @property string|null $from_status
 * @property string $to_status
 * @property int|null $changed_by_user_id
 * @property string|null $note
 * @property \Illuminate\Support\Carbon $created_at
 */
class ContainerStatusHistory extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'container_id',
        'from_status',
        'to_status',
        'changed_by_user_id',
        'note',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Container, $this> */
    public function container(): BelongsTo
    {
        return $this->belongsTo(Container::class);
    }

    /** @return BelongsTo<User, $this> */
    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by_user_id');
    }

    public function toStatusLabel(): string
    {
        return ContainerStatus::label($this->to_status);
    }
}
