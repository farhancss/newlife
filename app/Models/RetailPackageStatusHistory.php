<?php

namespace App\Models;

use App\Enums\RetailPackageStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $retail_package_id
 * @property string|null $from_status
 * @property string $to_status
 * @property int|null $changed_by_user_id
 * @property string|null $note
 * @property \Illuminate\Support\Carbon $created_at
 */
class RetailPackageStatusHistory extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'retail_package_id',
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

    /** @return BelongsTo<RetailPackage, $this> */
    public function retailPackage(): BelongsTo
    {
        return $this->belongsTo(RetailPackage::class);
    }

    /** @return BelongsTo<User, $this> */
    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by_user_id');
    }

    public function toStatusLabel(): string
    {
        return RetailPackageStatus::label($this->to_status);
    }
}
