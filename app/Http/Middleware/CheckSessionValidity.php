<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckSessionValidity
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if ($user) {
            // If session version is not set but we have login data (from 2FA flow), restore it
            if (!session()->has('session_version') && session()->has('login.id')) {
                session()->put('session_version', $user->session_version);
            }

            if (!$user->isSessionValid()) {
                // Session is invalid, log out the user
                Auth::logout();

                // Clear the session
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                // Redirect to login with a message
                return redirect()->route('login')->with('error', 'Your session has expired. Please log in again.');
            }
        }

        return $next($request);
    }
}
