<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Mail\ResetPasswordMail;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

/**
 * @property string $role
 * @property string $status
 * @property string|null $avatar_path
 * @property bool $must_reset_password
 * @property \Illuminate\Support\Carbon|null $password_changed_at
 * @property string|null $squarespace_contact_id
 * @property-read StudentProfile|null $studentProfile
 */
class User extends Authenticatable implements CanResetPasswordContract
{
    use CanResetPassword;
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
        'avatar_path',
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

    /** @return \Illuminate\Database\Eloquent\Relations\HasMany<PortalNotification, $this> */
    public function portalNotifications(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(PortalNotification::class)->latest();
    }

    /** @return \Illuminate\Database\Eloquent\Relations\HasOne<NotificationPreference, $this> */
    public function notificationPreference(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(NotificationPreference::class);
    }

    /**
     * Public URL for the uploaded profile photo, or null to fall back to initials.
     */
    public function avatarUrl(): ?string
    {
        if ($this->avatar_path === null || $this->avatar_path === '') {
            return null;
        }

        return \Illuminate\Support\Facades\Storage::disk((string) config('portal.avatars.disk', 'public'))
            ->url($this->avatar_path);
    }

    /**
     * Up to two uppercase initials derived from the display name.
     */
    public function initials(): string
    {
        $initials = collect(explode(' ', (string) $this->name))
            ->filter()
            ->take(2)
            ->map(fn (string $part): string => strtoupper(substr($part, 0, 1)))
            ->join('');

        return $initials !== '' ? $initials : 'NL';
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

    public function sendPasswordResetNotification($token): void
    {
        $resetUrl = URL::route('password.reset', [
            'token' => $token,
            'email' => $this->getEmailForPasswordReset(),
        ]);

        Mail::to($this->email)->queue(new ResetPasswordMail($this, $resetUrl));
    }
}
