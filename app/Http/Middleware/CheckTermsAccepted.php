<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckTermsAccepted
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if ($user->terms_accepted_count === 0) {
            return redirect()->route('terms.show');
        }

        return $next($request);
    }
}