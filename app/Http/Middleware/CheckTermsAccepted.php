<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckTermsAccepted
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        \Log::info('CheckTermsAccepted middleware', [
            'user_id' => $user->id,
            'email' => $user->email,
            'terms_accepted' => $user->terms_accepted,
            'terms_accepted_type' => gettype($user->terms_accepted),
            'route' => $request->route() ? $request->route()->getName() : 'unknown',
        ]);

        if ($user->terms_accepted === false || $user->terms_accepted === null) {
            \Log::info('CheckTermsAccepted: Redirecting to terms.show', [
                'user_id' => $user->id,
                'terms_accepted' => $user->terms_accepted,
            ]);
            return redirect()->route('terms.show');
        }

        \Log::info('CheckTermsAccepted: Allowing access', [
            'user_id' => $user->id,
            'terms_accepted' => $user->terms_accepted,
        ]);

        return $next($request);
    }
}