<?php

namespace App\Policies;

use App\Models\Idea;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class IdeaPolicy
{
    /**
     * Determine whether the user can manage collaboration for the idea.
     * Only the idea author can manage collaboration settings.
     */
    public function manageCollaboration(User $user, Idea $idea): bool
    {
        return $user->id === $idea->user_id;
    }

    /**
     * Determine whether the user can invite collaborators to the idea.
     * Only the idea author can invite collaborators.
     */
    public function inviteCollaborators(User $user, Idea $idea): bool
    {
        return $user->id === $idea->user_id;
    }

    /**
     * Determine whether the user can manage collaborators for the idea.
     * Only the idea author can manage (add/remove/update) collaborators.
     */
    public function manageCollaborators(User $user, Idea $idea): bool
    {
        return $user->id === $idea->user_id;
    }

    /**
     * Determine whether the user can view collaboration requests for their own ideas.
     * Only the idea author can view requests sent to their ideas.
     */
    public function viewCollaborationRequests(User $user, Idea $idea): bool
    {
        return $user->id === $idea->user_id;
    }

    /**
     * Determine whether the user can respond to collaboration requests sent to them.
     * Users can respond to requests sent to them (not necessarily idea authors).
     */
    public function respondToCollaborationRequests(User $user): bool
    {
        return true; // Any authenticated user can respond to requests sent to them
    }

    /**
     * Determine whether the user can view their own collaboration activity.
     * Users can view their own collaboration dashboard.
     */
    public function viewCollaborationActivity(User $user): bool
    {
        return true; // Any authenticated user can view their own activity
    }
}
