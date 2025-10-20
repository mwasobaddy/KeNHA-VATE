<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class IdeaCollaborationRequest extends Model
{
    protected $fillable = [
        'idea_id',
        'collaborator_user_id',
        'request_message',
        'status',
        'requested_at',
        'response_at',
        'response_message',
    ];

    protected $casts = [
        'requested_at' => 'datetime',
        'response_at' => 'datetime',
    ];

    // Relationships
    public function idea(): BelongsTo
    {
        return $this->belongsTo(Idea::class);
    }

    public function collaboratorUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'collaborator_user_id');
    }

    // Scopes
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    public function scopeAccepted(Builder $query): Builder
    {
        return $query->where('status', 'accepted');
    }

    public function scopeDeclined(Builder $query): Builder
    {
        return $query->where('status', 'declined');
    }

    public function scopeForIdea(Builder $query, int $ideaId): Builder
    {
        return $query->where('idea_id', $ideaId);
    }

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('collaborator_user_id', $userId);
    }

    // Methods
    public function accept(): bool
    {
        if ($this->status !== 'pending') {
            return false;
        }

        return $this->update([
            'status' => 'accepted',
            'response_at' => now(),
        ]);
    }

    public function decline(string $reason = null): bool
    {
        if ($this->status !== 'pending') {
            return false;
        }

        return $this->update([
            'status' => 'declined',
            'response_at' => now(),
            'response_message' => $reason,
        ]);
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isAccepted(): bool
    {
        return $this->status === 'accepted';
    }

    public function isDeclined(): bool
    {
        return $this->status === 'declined';
    }

    public function getStatusLabel(): string
    {
        return match ($this->status) {
            'pending' => 'Pending Response',
            'accepted' => 'Accepted',
            'declined' => 'Declined',
            default => 'Unknown',
        };
    }

    public function getDaysSinceRequested(): int
    {
        return $this->requested_at ? $this->requested_at->diffInDays(now()) : 0;
    }

    public function hasResponse(): bool
    {
        return !is_null($this->response_at);
    }
}
