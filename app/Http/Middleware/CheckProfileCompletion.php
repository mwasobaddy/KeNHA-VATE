<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckProfileCompletion
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if (!$user->staff || !$user->staff->isProfileComplete()) {
            return redirect()->route('profile.setup');
        }

        return $next($request);
    }
}