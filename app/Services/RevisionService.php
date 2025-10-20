<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Idea;
use App\Models\IdeaRevision;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class RevisionService
{
    public function __construct(
        private readonly AuditService $auditService,
        private readonly NotificationService $notificationService,
        private readonly PointService $pointService
    ) {}

    /**
     * Create a new revision for an idea.
     */
    public function createRevision(
        Idea $idea,
        array $changes,
        string $summary,
        User $user,
        string $type = 'author'
    ): IdeaRevision {
        return DB::transaction(function () use ($idea, $changes, $summary, $user, $type) {
            // Create the revision
            $revision = $idea->createRevision($changes, $summary, $user, $type);

            // Audit the revision creation
            $this->auditService->logUserActivity($user, 'revision_created', [
                'idea_id' => $idea->id,
                'revision_id' => $revision->id,
                'revision_number' => $revision->revision_number,
                'revision_type' => $type,
                'change_summary' => $summary,
                'changed_fields_count' => count($changes),
            ]);

            // Award points for creating revision
            if ($type === 'collaborator') {
                $this->pointService->awardCollaborationPoints($user, 'suggest_revision');
            }

            // Notify relevant users
            if ($type === 'collaborator') {
                // Notify the author about the new revision suggestion
                $this->notificationService->notify(
                    $idea->user,
                    'info',
                    'New Revision Suggestion',
                    "A collaborator has suggested changes to your idea '{$idea->idea_title}'.",
                    route('ideas.show', $idea->slug) . '#revisions'
                );
            }

            return $revision;
        });
    }

    /**
     * Accept a pending revision.
     */
    public function acceptRevision(IdeaRevision $revision, User $author): bool
    {
        return DB::transaction(function () use ($revision, $author) {
            $success = $revision->idea->acceptRevision($revision, $author);

            if ($success) {
                // Audit the acceptance
                $this->auditService->logUserActivity($author, 'revision_accepted', [
                    'idea_id' => $revision->idea_id,
                    'revision_id' => $revision->id,
                    'revision_number' => $revision->revision_number,
                    'accepted_by_user_id' => $author->id,
                ]);

                // Award points to the revision creator
                if ($revision->revision_type === 'collaborator') {
                    $this->pointService->awardCollaborationPoints($revision->createdByUser, 'revision_accepted');
                }

                // Notify the revision creator
                $this->notificationService->notify(
                    $revision->createdByUser,
                    'success',
                    'Revision Accepted',
                    "Your suggested changes to '{$revision->idea->idea_title}' have been accepted!",
                    route('ideas.show', $revision->idea->slug)
                );
            }

            return $success;
        });
    }

    /**
     * Reject a pending revision.
     */
    public function rejectRevision(IdeaRevision $revision, User $author, string $reason = null): bool
    {
        return DB::transaction(function () use ($revision, $author, $reason) {
            $success = $revision->idea->rejectRevision($revision, $author, $reason);

            if ($success) {
                // Audit the rejection
                $this->auditService->logUserActivity($author, 'revision_rejected', [
                    'idea_id' => $revision->idea_id,
                    'revision_id' => $revision->id,
                    'revision_number' => $revision->revision_number,
                    'rejected_by_user_id' => $author->id,
                    'rejection_reason' => $reason,
                ]);

                // Notify the revision creator
                $this->notificationService->notify(
                    $revision->createdByUser,
                    'warning',
                    'Revision Rejected',
                    "Your suggested changes to '{$revision->idea->idea_title}' were not accepted." .
                    ($reason ? " Reason: {$reason}" : ''),
                    route('ideas.show', $revision->idea->slug)
                );
            }

            return $success;
        });
    }

    /**
     * Rollback to a specific revision.
     */
    public function rollbackToRevision(Idea $idea, int $revisionNumber, User $author): bool
    {
        return DB::transaction(function () use ($idea, $revisionNumber, $author) {
            $success = $idea->rollbackToRevision($revisionNumber, $author);

            if ($success) {
                // Audit the rollback
                $this->auditService->logUserActivity($author, 'revision_rollback', [
                    'idea_id' => $idea->id,
                    'rollback_to_revision' => $revisionNumber,
                    'current_revision_after_rollback' => $idea->fresh()->current_revision_number,
                    'rollback_by_user_id' => $author->id,
                ]);

                // Notify active collaborators about the rollback
                foreach ($idea->activeCollaborators as $collaborator) {
                    $this->notificationService->notify(
                        $collaborator->user,
                        'info',
                        'Idea Revision Rollback',
                        "The author of '{$idea->idea_title}' has rolled back to an earlier version.",
                        route('ideas.show', $idea->slug)
                    );
                }
            }

            return $success;
        });
    }

    /**
     * Get revision history for an idea.
     */
    public function getRevisionHistory(Idea $idea, int $limit = 50): Collection
    {
        return $idea->getRevisionHistory($limit);
    }

    /**
     * Compare two revisions and return the differences.
     */
    public function compareRevisions(IdeaRevision $revision1, IdeaRevision $revision2): array
    {
        if ($revision1->idea_id !== $revision2->idea_id) {
            throw new \InvalidArgumentException('Revisions must belong to the same idea');
        }

        $changes1 = $revision1->getChangedFields();
        $changes2 = $revision2->getChangedFields();

        $differences = [];

        // Find fields that changed between revisions
        $allFields = array_unique(array_merge(array_keys($changes1), array_keys($changes2)));

        foreach ($allFields as $field) {
            $value1 = $changes1[$field] ?? null;
            $value2 = $changes2[$field] ?? null;

            if ($value1 !== $value2) {
                $differences[$field] = [
                    'revision_' . $revision1->revision_number => $value1,
                    'revision_' . $revision2->revision_number => $value2,
                ];
            }
        }

        return [
            'revision_1' => [
                'number' => $revision1->revision_number,
                'created_by' => $revision1->createdByUser->name ?? 'Unknown',
                'created_at' => $revision1->created_at,
                'type' => $revision1->revision_type,
            ],
            'revision_2' => [
                'number' => $revision2->revision_number,
                'created_by' => $revision2->createdByUser->name ?? 'Unknown',
                'created_at' => $revision2->created_at,
                'type' => $revision2->revision_type,
            ],
            'differences' => $differences,
        ];
    }

    /**
     * Get pending revisions count for an idea.
     */
    public function getPendingRevisionsCount(Idea $idea): int
    {
        return $idea->revisions()->pending()->count();
    }

    /**
     * Get revisions statistics for an idea.
     */
    public function getRevisionStats(Idea $idea): array
    {
        $revisions = $idea->revisions();

        return [
            'total_revisions' => $revisions->count(),
            'pending_revisions' => $revisions->pending()->count(),
            'accepted_revisions' => $revisions->accepted()->count(),
            'rejected_revisions' => $revisions->rejected()->count(),
            'author_revisions' => $revisions->byType('author')->count(),
            'collaborator_revisions' => $revisions->byType('collaborator')->count(),
            'rollback_revisions' => $revisions->byType('rollback')->count(),
        ];
    }
}