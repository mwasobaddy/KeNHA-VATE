<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Idea;
use App\Models\IdeaCollaborator;
use App\Models\IdeaCollaborationRequest;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CollaborationService
{
    public function __construct(
        private readonly AuditService $auditService,
        private readonly NotificationService $notificationService,
        private readonly PointService $pointService
    ) {}

    /**
     * Send an invitation to collaborate on an idea.
     */
    public function sendInvitation(
        Idea $idea,
        User $invitee,
        ?string $message = null
    ): IdeaCollaboration {
        return DB::transaction(function () use ($idea, $invitee, $inviter, $permissions, $message) {
            // Check if invitation already exists
            $existingRequest = $idea->collaborationRequests()
                ->where('invitee_id', $invitee->id)
                ->where('status', 'pending')
                ->first();

            if ($existingRequest) {
                throw new \InvalidArgumentException('An invitation is already pending for this user');
            }

            // Check if user is already a collaborator
            if ($idea->isCollaborator($invitee)) {
                throw new \InvalidArgumentException('User is already a collaborator on this idea');
            }

            // Create the invitation request
            $request = $idea->createCollaborationRequest($invitee, $inviter, $permissions, $message);

            // Audit the invitation
            $this->auditService->logUserActivity($inviter, 'collaboration_invitation_sent', [
                'idea_id' => $idea->id,
                'invitee_id' => $invitee->id,
                'request_id' => $request->id,
                'permissions' => $permissions,
                'invitation_message' => $message,
            ]);

            // Notify the invitee
            $this->notificationService->notify(
                $invitee,
                'info',
                'Collaboration Invitation',
                "You've been invited to collaborate on '{$idea->idea_title}' by {$inviter->name}.",
                route('ideas.collaboration.requests')
            );

            return $request;
        });
    }

    /**
     * Submit a collaboration request to an idea author.
     */
    public function submitCollaborationRequest(
        Idea $idea,
        User $requester,
        string $requestMessage,
        ?string $proposedContribution = null,
        ?string $experience = null
    ): IdeaCollaborationRequest {
        return DB::transaction(function () use ($idea, $requester, $requestMessage, $proposedContribution, $experience) {
            // Validate that collaboration is enabled
            if (!$idea->isCollaborationEnabled()) {
                throw new \InvalidArgumentException('Collaboration is not enabled for this idea');
            }

            // Check if user is the author
            if ($idea->isAuthor($requester)) {
                throw new \InvalidArgumentException('You cannot request collaboration on your own idea');
            }

            // Check if user is already a collaborator
            if ($idea->isCollaborator($requester)) {
                throw new \InvalidArgumentException('You are already a collaborator on this idea');
            }

            // Check if a pending request already exists
            $existingRequest = $idea->collaborationRequests()
                ->where('requester_id', $requester->id)
                ->where('status', 'pending')
                ->first();

            if ($existingRequest) {
                throw new \InvalidArgumentException('You already have a pending collaboration request for this idea');
            }

            // Create the collaboration request
            $request = IdeaCollaborationRequest::create([
                'idea_id' => $idea->id,
                'requester_id' => $requester->id,
                'request_message' => $requestMessage,
                'proposed_contribution' => $proposedContribution,
                'requester_experience' => $experience,
                'status' => 'pending',
                'requested_at' => now(),
            ]);

            // Audit the request
            $this->auditService->logUserActivity($requester, 'collaboration_request_submitted', [
                'idea_id' => $idea->id,
                'request_id' => $request->id,
                'request_message' => $requestMessage,
                'proposed_contribution' => $proposedContribution,
                'requester_experience' => $experience,
            ]);

            // Notify the idea author
            $this->notificationService->notify(
                $idea->user,
                'info',
                'New Collaboration Request',
                "{$requester->name} has requested to collaborate on your idea '{$idea->idea_title}'.",
                route('ideas.collaboration.requests')
            );

            return $request;
        });
    }

    /**
     * Accept a collaboration invitation.
     */
    public function acceptInvitation(IdeaCollaborationRequest $request, User $invitee): IdeaCollaborator
    {
        return DB::transaction(function () use ($request, $invitee) {
            // Accept the request
            $collaborator = $request->accept($invitee);

            // Audit the acceptance
            $this->auditService->logUserActivity($invitee, 'collaboration_invitation_accepted', [
                'idea_id' => $request->idea_id,
                'request_id' => $request->id,
                'inviter_id' => $request->inviter_id,
                'permissions' => $request->permissions,
            ]);

            // Award points for accepting collaboration
            $this->pointService->awardCollaborationPoints($invitee, 'accept_invitation');

            // Notify the inviter
            $this->notificationService->notify(
                $request->inviter,
                'success',
                'Collaboration Invitation Accepted',
                "{$invitee->name} has accepted your invitation to collaborate on '{$request->idea->idea_title}'!",
                route('ideas.show', $request->idea->slug)
            );

            return $collaborator;
        });
    }

    /**
     * Decline a collaboration invitation.
     */
    public function declineInvitation(IdeaCollaborationRequest $request, User $invitee, ?string $reason = null): bool
    {
        return DB::transaction(function () use ($request, $invitee, $reason) {
            $success = $request->decline($invitee, $reason);

            if ($success) {
                // Audit the decline
                $this->auditService->logUserActivity($invitee, 'collaboration_invitation_declined', [
                    'idea_id' => $request->idea_id,
                    'request_id' => $request->id,
                    'inviter_id' => $request->inviter_id,
                    'decline_reason' => $reason,
                ]);

                // Notify the inviter
                $this->notificationService->notify(
                    $request->inviter,
                    'warning',
                    'Collaboration Invitation Declined',
                    "{$invitee->name} has declined your invitation to collaborate on '{$request->idea->idea_title}'." .
                    ($reason ? " Reason: {$reason}" : ''),
                    route('ideas.show', $request->idea->slug)
                );
            }

            return $success;
        });
    }

    /**
     * Accept a collaboration request from a user.
     */
    public function acceptRequest(IdeaCollaborationRequest $request, User $author): IdeaCollaborator
    {
        return DB::transaction(function () use ($request, $author) {
            // Verify the author owns the idea
            if ($request->idea->user_id !== $author->id) {
                throw new \InvalidArgumentException('Only the idea author can accept collaboration requests.');
            }

            // Accept the request and create collaborator
            $collaborator = IdeaCollaborator::create([
                'idea_id' => $request->idea_id,
                'user_id' => $request->requester_id,
                'permission_level' => 'suggest', // Default permission level
                'invited_by_user_id' => $author->id, // The author is accepting the request
                'status' => 'active',
                'accepted_at' => now(),
            ]);

            // Update the request status
            $request->update([
                'status' => 'accepted',
                'response_at' => now(),
            ]);

            // Audit the acceptance
            $this->auditService->logUserActivity($author, 'collaboration_request_accepted', [
                'idea_id' => $request->idea_id,
                'request_id' => $request->id,
                'requester_id' => $request->requester_id,
                'collaborator_id' => $collaborator->id,
            ]);

            // Award points for successful collaboration
            $this->pointService->awardCollaborationPoints($request->requester, 'request_accepted');

            // Notify the requester
            $this->notificationService->notify(
                $request->requester,
                'success',
                'Collaboration Request Accepted',
                "{$author->name} has accepted your collaboration request for '{$request->idea->idea_title}'! You can now contribute to this idea.",
                route('ideas.show', $request->idea->slug)
            );

            return $collaborator;
        });
    }

    /**
     * Decline a collaboration request from a user.
     */
    public function declineRequest(IdeaCollaborationRequest $request, User $author, ?string $reason = null): bool
    {
        return DB::transaction(function () use ($request, $author, $reason) {
            // Verify the author owns the idea
            if ($request->idea->user_id !== $author->id) {
                throw new \InvalidArgumentException('Only the idea author can decline collaboration requests.');
            }

            // Decline the request
            $success = $request->update([
                'status' => 'declined',
                'response_at' => now(),
                'response_message' => $reason,
            ]);

            if ($success) {
                // Audit the decline
                $this->auditService->logUserActivity($author, 'collaboration_request_declined', [
                    'idea_id' => $request->idea_id,
                    'request_id' => $request->id,
                    'requester_id' => $request->requester_id,
                    'decline_reason' => $reason,
                ]);

                // Notify the requester
                $this->notificationService->notify(
                    $request->requester,
                    'warning',
                    'Collaboration Request Declined',
                    "{$author->name} has declined your collaboration request for '{$request->idea->idea_title}'." .
                    ($reason ? " Reason: {$reason}" : ''),
                    route('ideas.show', $request->idea->slug)
                );
            }

            return $success;
        });
    }

    /**
     * Update collaborator permissions.
     */
    public function updatePermissions(
        IdeaCollaborator $collaborator,
        string $permissions,
        User $updatedBy
    ): bool {
        return DB::transaction(function () use ($collaborator, $permissions, $updatedBy) {
            $oldPermissions = $collaborator->permissions;
            $success = $collaborator->updatePermissions($permissions, $updatedBy);

            if ($success) {
                // Audit the permission change
                $this->auditService->logUserActivity($updatedBy, 'collaborator_permissions_updated', [
                    'idea_id' => $collaborator->idea_id,
                    'collaborator_id' => $collaborator->id,
                    'collaborator_user_id' => $collaborator->user_id,
                    'old_permissions' => $oldPermissions,
                    'new_permissions' => $permissions,
                ]);

                // Notify the collaborator
                $this->notificationService->notify(
                    $collaborator->user,
                    'info',
                    'Collaboration Permissions Updated',
                    "Your permissions for '{$collaborator->idea->idea_title}' have been updated to: {$permissions}",
                    route('ideas.show', $collaborator->idea->slug)
                );
            }

            return $success;
        });
    }

    /**
     * Remove a collaborator from an idea.
     */
    public function removeCollaborator(IdeaCollaborator $collaborator, User $removedBy, ?string $reason = null): bool
    {
        return DB::transaction(function () use ($collaborator, $removedBy, $reason) {
            $success = $collaborator->remove($removedBy, $reason);

            if ($success) {
                // Audit the removal
                $this->auditService->logUserActivity($removedBy, 'collaborator_removed', [
                    'idea_id' => $collaborator->idea_id,
                    'collaborator_id' => $collaborator->id,
                    'collaborator_user_id' => $collaborator->user_id,
                    'removal_reason' => $reason,
                ]);

                // Notify the removed collaborator
                $this->notificationService->notify(
                    $collaborator->user,
                    'warning',
                    'Removed from Collaboration',
                    "You have been removed from collaborating on '{$collaborator->idea->idea_title}'." .
                    ($reason ? " Reason: {$reason}" : ''),
                    route('ideas.show', $collaborator->idea->slug)
                );
            }

            return $success;
        });
    }

    /**
     * Get collaboration statistics for an idea.
     */
    public function getCollaborationStats(Idea $idea): array
    {
        $requests = $idea->collaborationRequests();
        $collaborators = $idea->collaborators();

        return [
            'total_collaborators' => $collaborators->count(),
            'active_collaborators' => $collaborators->active()->count(),
            'pending_invitations' => $requests->pending()->count(),
            'accepted_invitations' => $requests->accepted()->count(),
            'declined_invitations' => $requests->declined()->count(),
            'permission_breakdown' => [
                'read' => $collaborators->withPermissions('read')->count(),
                'comment' => $collaborators->withPermissions('comment')->count(),
                'edit' => $collaborators->withPermissions('edit')->count(),
                'admin' => $collaborators->withPermissions('admin')->count(),
            ],
        ];
    }

    /**
     * Get pending collaboration requests for a user.
     */
    public function getPendingRequestsForUser(User $user): Collection
    {
        return IdeaCollaborationRequest::where('invitee_id', $user->id)
            ->where('status', 'pending')
            ->with(['idea', 'inviter'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Check if a user can collaborate on an idea.
     */
    public function canUserCollaborate(User $user, Idea $idea, string $requiredPermission = 'read'): bool
    {
        // Author always has full access
        if ($idea->user_id === $user->id) {
            return true;
        }

        // Check if user is an active collaborator with required permission
        $collaborator = $idea->collaborators()
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->first();

        if (!$collaborator) {
            return false;
        }

        return $collaborator->hasPermission($requiredPermission);
    }

    /**
     * Get collaborators for an idea with their permissions.
     */
    public function getCollaboratorsWithPermissions(Idea $idea): Collection
    {
        return $idea->activeCollaborators()
            ->with('user:id,email,name')
            ->get()
            ->map(function ($collaborator) {
                return [
                    'id' => $collaborator->id,
                    'user' => $collaborator->user,
                    'permissions' => $collaborator->permissions,
                    'joined_at' => $collaborator->created_at,
                    'can_read' => $collaborator->hasPermission('read'),
                    'can_comment' => $collaborator->hasPermission('comment'),
                    'can_edit' => $collaborator->hasPermission('edit'),
                    'can_admin' => $collaborator->hasPermission('admin'),
                ];
            });
    }
}