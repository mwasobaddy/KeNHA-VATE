<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckAccountStatus
{
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();

        if (!$user) {
            return $next($request);
        }

        return match ($user->account_status) {
            'banned' => redirect()->route('account.banned'),
            'disabled' => redirect()->route('account.disabled'),
            'active' => $next($request),
            default => abort(403, 'Invalid account status'),
        };
    }
}