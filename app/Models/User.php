<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * @property string $role
 * @property string $status
 * @property bool $must_reset_password
 * @property \Illuminate\Support\Carbon|null $password_changed_at
 * @property string|null $squarespace_contact_id
 * @property-read StudentProfile|null $studentProfile
 */
class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory;
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'role',
        'status',
        'password',
        'must_reset_password',
        'password_changed_at',
        'squarespace_contact_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'must_reset_password' => 'boolean',
            'password_changed_at' => 'datetime',
        ];
    }

    public function studentProfile(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(StudentProfile::class);
    }

    public function isStudent(): bool
    {
        return $this->role === \App\Enums\UserRole::STUDENT;
    }

    public function isSuspended(): bool
    {
        return $this->status === \App\Enums\UserStatus::SUSPENDED;
    }

    public function isActive(): bool
    {
        return $this->status === \App\Enums\UserStatus::ACTIVE;
    }
}
