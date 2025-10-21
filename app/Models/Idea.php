<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Idea extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'ideas';

    protected $fillable = [
        'idea_title',
        'slug',
        'thematic_area_id',
        'abstract',
        'problem_statement',
        'proposed_solution',
        'cost_benefit_analysis',
        'declaration_of_interests',
        'original_idea_disclaimer',
        'collaboration_enabled',
        'team_effort',
        'team_members',
        'attachment',
        'attachment_filename',
        'attachment_mime',
        'attachment_size',
        'status',
        'user_id',
        'current_revision_number',
        'collaboration_deadline',
    ];

    protected $casts = [
        'original_idea_disclaimer' => 'boolean',
        'collaboration_enabled' => 'boolean',
        'team_effort' => 'boolean',
        'team_members' => 'array',
        'current_revision_number' => 'integer',
        'collaboration_deadline' => 'date',
    ];

    protected static function booted()
    {
        static::creating(function (Idea $idea) {
            if (empty($idea->slug)) {
                $base = Str::slug(substr($idea->idea_title ?: 'idea', 0, 50));
                $slug = $base;
                $counter = 1;
                while (self::where('slug', $slug)->exists()) {
                    $slug = $base . '-' . $counter++;
                }
                $idea->slug = $slug;
            }
        });
    }

    /**
     * Mark idea as draft.
     */
    public function markDraft(): void
    {
        $this->update(['status' => 'draft']);
    }

    /**
     * Mark idea as submitted.
     */
    public function markSubmitted(): void
    {
        $this->update(['status' => 'submitted']);
    }

    /**
     * Return attachment as a base64 data URI for inline previews (UI only)
     */
    public function getAttachmentDataUriAttribute(): ?string
    {
        if (!$this->attachment || !$this->attachment_mime) {
            return null;
        }

        return 'data:' . $this->attachment_mime . ';base64,' . base64_encode($this->attachment);
    }

    /**
     * Get the thematic area that owns the idea.
     */
    public function thematicArea(): BelongsTo
    {
        return $this->belongsTo(ThematicArea::class);
    }

    /**
     * Get the user that owns the idea.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the comments for the idea.
     */
    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    /**
     * Get the likes for the idea.
     */
    public function likes(): HasMany
    {
        return $this->hasMany(IdeaLike::class);
    }

    /**
     * Get the likes count dynamically.
     */
    public function getLikesCountAttribute(): int
    {
        return $this->likes()->count();
    }

    /**
     * Check if the idea is liked by a specific user.
     */
    public function isLikedBy(?int $userId): bool
    {
        if (!$userId) {
            return false;
        }

        return $this->likes()->where('user_id', $userId)->exists();
    }

    // Collaboration Relationships

    /**
     * Get the revisions for the idea.
     */
    public function revisions(): HasMany
    {
        return $this->hasMany(IdeaRevision::class);
    }

    /**
     * Get the collaborators for the idea.
     */
    public function collaborators(): HasMany
    {
        return $this->hasMany(IdeaCollaborator::class);
    }

    /**
     * Get the active collaborators for the idea.
     */
    public function activeCollaborators(): HasMany
    {
        return $this->collaborators()->active();
    }

    /**
     * Get the collaboration requests for the idea.
     */
    public function collaborationRequests(): HasMany
    {
        return $this->hasMany(IdeaCollaborationRequest::class);
    }

    /**
     * Get pending collaboration requests for the idea.
     */
    public function pendingCollaborationRequests(): HasMany
    {
        return $this->collaborationRequests()->pending();
    }

    // Collaboration Methods

    /**
     * Enable collaboration for this idea.
     */
    public function enableCollaboration(?\Carbon\Carbon $deadline = null): bool
    {
        return $this->update([
            'collaboration_enabled' => true,
            'collaboration_deadline' => $deadline,
        ]);
    }

    /**
     * Disable collaboration for this idea.
     */
    public function disableCollaboration(): bool
    {
        return $this->update([
            'collaboration_enabled' => false,
            'collaboration_deadline' => null,
        ]);
    }

    /**
     * Check if collaboration is enabled and active.
     */
    public function isCollaborationEnabled(): bool
    {
        if (!$this->collaboration_enabled) {
            return false;
        }

        // Check if deadline has passed
        if ($this->collaboration_deadline && $this->collaboration_deadline->isPast()) {
            return false;
        }

        return true;
    }

    /**
     * Check if a user is the author of this idea.
     */
    public function isAuthor(User $user): bool
    {
        return $this->user_id === $user->id;
    }

    /**
     * Check if a user is a collaborator on this idea.
     */
    public function isCollaborator(User $user): bool
    {
        return $this->collaborators()->where('user_id', $user->id)->active()->exists();
    }

    /**
     * Check if a user can edit this idea.
     */
    public function canUserEdit(User $user): bool
    {
        // Author can always edit
        if ($this->isAuthor($user)) {
            return true;
        }

        // Check if user is an active collaborator with edit permissions
        return $this->collaborators()
            ->where('user_id', $user->id)
            ->active()
            ->where('permission_level', 'edit')
            ->exists();
    }

    /**
     * Check if a user can suggest edits to this idea.
     */
    public function canUserSuggest(User $user): bool
    {
        // Author can always suggest (though they can edit directly)
        if ($this->isAuthor($user)) {
            return true;
        }

        // Check if user is an active collaborator
        return $this->collaborators()
            ->where('user_id', $user->id)
            ->active()
            ->exists();
    }

    /**
     * Create a new revision for this idea.
     */
    public function createRevision(array $changes, string $summary, User $user, string $type = 'author'): IdeaRevision
    {
        $revisionNumber = $this->current_revision_number + 1;

        $revision = $this->revisions()->create([
            'revision_number' => $revisionNumber,
            'changed_fields' => $changes,
            'change_summary' => $summary,
            'created_by_user_id' => $user->id,
            'revision_type' => $type,
            'status' => $type === 'author' ? 'accepted' : 'pending', // Author edits are auto-accepted
        ]);

        // Update current revision number if this is an author edit
        if ($type === 'author') {
            $this->update(['current_revision_number' => $revisionNumber]);
        }

        return $revision;
    }

    /**
     * Accept a pending revision.
     */
    public function acceptRevision(IdeaRevision $revision, User $author): bool
    {
        if (!$this->isAuthor($author) || !$revision->isPending()) {
            return false;
        }

        $revision->accept();
        $this->update(['current_revision_number' => $revision->revision_number]);

        return true;
    }

    /**
     * Reject a pending revision.
     */
    public function rejectRevision(IdeaRevision $revision, User $author, ?string $reason = null): bool
    {
        if (!$this->isAuthor($author) || !$revision->isPending()) {
            return false;
        }

        return $revision->reject();
    }

    /**
     * Rollback to a specific revision.
     */
    public function rollbackToRevision(int $revisionNumber, User $author): bool
    {
        if (!$this->isAuthor($author)) {
            return false;
        }

        $targetRevision = $this->revisions()->where('revision_number', $revisionNumber)->first();

        if (!$targetRevision) {
            return false;
        }

        // Create a rollback revision
        $this->revisions()->create([
            'revision_number' => $this->current_revision_number + 1,
            'changed_fields' => ['rollback_to' => $revisionNumber],
            'change_summary' => "Rolled back to revision {$revisionNumber}",
            'created_by_user_id' => $author->id,
            'revision_type' => 'rollback',
            'status' => 'accepted',
        ]);

        $this->update(['current_revision_number' => $this->current_revision_number + 1]);

        return true;
    }

    /**
     * Get the latest revision for this idea.
     */
    public function latestRevision(): ?IdeaRevision
    {
        return $this->revisions()->orderBy('revision_number', 'desc')->first();
    }

    /**
     * Get revision history for this idea.
     */
    public function getRevisionHistory(int $limit = 50): \Illuminate\Database\Eloquent\Collection
    {
        return $this->revisions()
            ->with('createdByUser')
            ->orderBy('revision_number', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get active collaborators count.
     */
    public function getActiveCollaboratorsCountAttribute(): int
    {
        return $this->activeCollaborators()->count();
    }

    /**
     * Get pending revisions count.
     */
    public function getPendingRevisionsCountAttribute(): int
    {
        return $this->revisions()->pending()->count();
    }
}
