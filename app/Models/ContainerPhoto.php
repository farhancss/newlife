<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * @property int $id
 * @property int $container_id
 * @property string $type
 * @property int|null $uploaded_by_user_id
 * @property string $disk
 * @property string $path
 * @property string|null $original_name
 * @property string|null $mime
 * @property int $size
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Container $container
 */
class ContainerPhoto extends Model
{
    /** Exterior photo uploaded by the student while packing. */
    public const TYPE_EXTERIOR = 'exterior';

    /** Evidence photo uploaded by an admin once received at the New Life hub. */
    public const TYPE_HUB_INTAKE = 'hub_intake';

    protected $fillable = [
        'container_id',
        'type',
        'uploaded_by_user_id',
        'disk',
        'path',
        'original_name',
        'mime',
        'size',
    ];

    protected $attributes = [
        'type' => self::TYPE_EXTERIOR,
    ];

    protected function casts(): array
    {
        return [
            'size' => 'integer',
        ];
    }

    /** @return BelongsTo<Container, $this> */
    public function container(): BelongsTo
    {
        return $this->belongsTo(Container::class);
    }

    /** @return BelongsTo<User, $this> */
    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by_user_id');
    }

    public function url(): string
    {
        return Storage::disk($this->disk)->url($this->path);
    }
}
