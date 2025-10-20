<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class IdeaRevision extends Model
{
    protected $fillable = [
        'idea_id',
        'revision_number',
        'changed_fields',
        'change_summary',
        'created_by_user_id',
        'revision_type',
        'status',
    ];

    protected $casts = [
        'changed_fields' => 'array',
        'revision_number' => 'integer',
    ];

    // Relationships
    public function idea(): BelongsTo
    {
        return $this->belongsTo(Idea::class);
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
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

    public function scopeRejected(Builder $query): Builder
    {
        return $query->where('status', 'rejected');
    }

    public function scopeByType(Builder $query, string $type): Builder
    {
        return $query->where('revision_type', $type);
    }

    public function scopeForIdea(Builder $query, int $ideaId): Builder
    {
        return $query->where('idea_id', $ideaId);
    }

    // Methods
    public function accept(): bool
    {
        if ($this->status !== 'pending') {
            return false;
        }

        return $this->update(['status' => 'accepted']);
    }

    public function reject(): bool
    {
        if ($this->status !== 'pending') {
            return false;
        }

        return $this->update(['status' => 'rejected']);
    }

    public function getChangedFields(): array
    {
        return $this->changed_fields ?? [];
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isAccepted(): bool
    {
        return $this->status === 'accepted';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    public function getRevisionTypeLabel(): string
    {
        return match ($this->revision_type) {
            'author' => 'Author Edit',
            'collaborator' => 'Collaborator Suggestion',
            'rollback' => 'Rollback',
            default => 'Unknown',
        };
    }

    public function getStatusLabel(): string
    {
        return match ($this->status) {
            'pending' => 'Pending Review',
            'accepted' => 'Accepted',
            'rejected' => 'Rejected',
            default => 'Unknown',
        };
    }
}
