<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckProfileCompletion
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        // Check if user has completed their basic profile
        if (!$this->isProfileComplete($user)) {
            return redirect()->route('profile.edit');
        }

        return $next($request);
    }

    /**
     * Check if user's profile is complete based on their category.
     */
    private function isProfileComplete($user): bool
    {
        // All users must have basic profile info
        if (empty($user->first_name) || empty($user->other_names) || empty($user->gender) || empty($user->mobile_phone)) {
            return false;
        }

        // If user doesn't have a staff record, they're a regular user and profile is complete
        if (!$user->staff) {
            return true;
        }

        // If user has staff record, check staff-specific completion requirements
        return $user->staff->isProfileComplete();
    }
}