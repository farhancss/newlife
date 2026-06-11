<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $slug
 * @property string $name
 * @property string|null $tagline
 * @property int $price_cents
 * @property int $container_count
 * @property bool $includes_move_out_cycle
 * @property bool $is_featured
 * @property int $sort_order
 * @property list<string>|null $features
 */
class Package extends Model
{
    protected $fillable = [
        'slug',
        'name',
        'tagline',
        'price_cents',
        'container_count',
        'includes_move_out_cycle',
        'is_featured',
        'sort_order',
        'features',
    ];

    protected function casts(): array
    {
        return [
            'price_cents' => 'integer',
            'container_count' => 'integer',
            'includes_move_out_cycle' => 'boolean',
            'is_featured' => 'boolean',
            'sort_order' => 'integer',
            'features' => 'array',
        ];
    }

    /** @return HasMany<StudentProfile, $this> */
    public function studentProfiles(): HasMany
    {
        return $this->hasMany(StudentProfile::class);
    }

    public function formattedPrice(): string
    {
        return '$' . number_format($this->price_cents / 100, 0);
    }

    public function shortLabel(): string
    {
        return match ($this->slug) {
            'essential' => 'Essential',
            'summit' => 'Summit',
            'legacy' => 'Legacy',
            default => $this->name,
        };
    }
}
