<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, TwoFactorAuthenticatable, SoftDeletes, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'username',
        'first_name',
        'other_names',
        'gender',
        'mobile_phone',
        'email',
        'google_id',
        'account_status',
        'terms_accepted',
        'terms_accepted_count',
        'last_terms_accepted_at',
        'current_terms_version',
        'points',
        'password',
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
            'last_terms_accepted_at' => 'datetime',
            'password' => 'hashed',
            'terms_accepted' => 'boolean',
        ];
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        if ($this->staff) {
            return Str::of($this->staff->full_name)
                ->explode(' ')
                ->take(2)
                ->map(fn ($word) => Str::substr($word, 0, 1))
                ->implode('');
        }

        return Str::of($this->username ?? $this->email)
            ->explode(' ')
            ->take(2)
            ->map(fn ($word) => Str::substr($word, 0, 1))
            ->implode('');
    }

    /**
     * Get the staff profile for this user.
     */
    public function staff(): HasOne
    {
        return $this->hasOne(Staff::class);
    }

    /**
     * Get the audit logs for this user.
     */
    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    /**
     * Get the notifications for this user.
     */
    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    /**
     * Get the unread notifications for this user.
     */
    public function unreadNotifications()
    {
        return $this->notifications()->unread();
    }

    /**
     * Get the point transactions for this user.
     */
    public function pointTransactions(): HasMany
    {
        return $this->hasMany(PointTransaction::class);
    }

    /**
     * Get users who have this user as their supervisor.
     */
    public function supervisedStaff(): HasMany
    {
        return $this->hasMany(Staff::class, 'supervisor_id');
    }

    /**
     * Check if the user is active.
     */
    public function isActive(): bool
    {
        return $this->account_status === 'active';
    }

    /**
     * Check if the user is banned.
     */
    public function isBanned(): bool
    {
        return $this->account_status === 'banned';
    }

    /**
     * Check if the user is disabled.
     */
    public function isDisabled(): bool
    {
        return $this->account_status === 'disabled';
    }

    /**
     * Check if the user has accepted terms and conditions.
     */
    public function hasAcceptedTerms(): bool
    {
        return $this->terms_accepted === true;
    }

    /**
     * Check if the user is KeNHA staff based on email domain.
     */
    public function isKenhaStaff(): bool
    {
        return str_ends_with($this->email, '@kenha.co.ke');
    }
}
