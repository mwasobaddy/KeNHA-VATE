<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class IdeaCollaborator extends Model
{
    protected $fillable = [
        'idea_id',
        'user_id',
        'permission_level',
        'invited_by_user_id',
        'status',
        'invited_at',
        'accepted_at',
        'removed_at',
    ];

    protected $casts = [
        'invited_at' => 'datetime',
        'accepted_at' => 'datetime',
        'removed_at' => 'datetime',
    ];

    // Relationships
    public function idea(): BelongsTo
    {
        return $this->belongsTo(Idea::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function invitedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by_user_id');
    }

    // Scopes
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    public function scopeRemoved(Builder $query): Builder
    {
        return $query->where('status', 'removed');
    }

    public function scopeByPermission(Builder $query, string $permission): Builder
    {
        return $query->where('permission_level', $permission);
    }

    public function scopeForIdea(Builder $query, int $ideaId): Builder
    {
        return $query->where('idea_id', $ideaId);
    }

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    // Methods
    public function acceptInvitation(): bool
    {
        if ($this->status !== 'pending') {
            return false;
        }

        return $this->update([
            'status' => 'active',
            'accepted_at' => now(),
        ]);
    }

    public function removeCollaborator(): bool
    {
        if ($this->status === 'removed') {
            return false;
        }

        return $this->update([
            'status' => 'removed',
            'removed_at' => now(),
        ]);
    }

    public function canEdit(): bool
    {
        return $this->status === 'active' && $this->permission_level === 'edit';
    }

    public function canSuggest(): bool
    {
        return $this->status === 'active' && in_array($this->permission_level, ['suggest', 'edit']);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isRemoved(): bool
    {
        return $this->status === 'removed';
    }

    public function getPermissionLevelLabel(): string
    {
        return match ($this->permission_level) {
            'suggest' => 'Can Suggest Edits',
            'edit' => 'Can Edit Directly',
            default => 'Unknown',
        };
    }

    public function getStatusLabel(): string
    {
        return match ($this->status) {
            'pending' => 'Invitation Pending',
            'active' => 'Active Collaborator',
            'removed' => 'Removed',
            default => 'Unknown',
        };
    }

    public function getDaysSinceInvited(): int
    {
        return $this->invited_at ? $this->invited_at->diffInDays(now()) : 0;
    }
}
