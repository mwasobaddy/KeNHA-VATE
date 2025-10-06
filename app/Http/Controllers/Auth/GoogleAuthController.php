<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Events\UserLoggedIn;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\RedirectResponse;

class GoogleAuthController
{
    public function __construct(
        private readonly AuditService $auditService
    ) {}

    /**
     * Redirect to Google OAuth.
     */
    public function redirect(): RedirectResponse
    {
        return Socialite::driver('google')->redirect();
    }

    /**
     * Handle Google OAuth callback.
     */
    public function callback(Request $request): RedirectResponse
    {
        try {
            $googleUser = Socialite::driver('google')->user();

            // Split Google name into first and other names
            $nameParts = explode(' ', $googleUser->getName(), 2);
            $firstName = $nameParts[0] ?? '';
            $otherNames = $nameParts[1] ?? '';

            // Check if user exists with this email
            $user = User::where('email', $googleUser->getEmail())->first();

            if ($user) {
                // Merge existing user with Google data
                $user->update([
                    'google_id' => $googleUser->getId(),
                    'username' => $googleUser->getName(),
                    'first_name' => $nameParts[0] ?? '',
                    'other_names' => $nameParts[1] ?? '',
                    'email_verified_at' => now(), // Google emails are verified
                ]);
            } else {
                // Create new user
                $user = User::create([
                    'username' => $googleUser->getName(),
                    'first_name' => $nameParts[0] ?? '',
                    'other_names' => $nameParts[1] ?? '',
                    'email' => $googleUser->getEmail(),
                    'google_id' => $googleUser->getId(),
                    'email_verified_at' => now(), // Google emails are verified
                    'account_status' => 'active',
                    'password' => Hash::make(str()->random(32)), // Random password since OAuth
                ]);
            }

            // Check account status
            if (!$user->isActive()) {
                if ($user->isBanned()) {
                    return redirect()->route('account.banned');
                }

                if ($user->isDisabled()) {
                    return redirect()->route('account.disabled');
                }
            }

            // Log the user in
            Auth::login($user, true);

            // Fire login event
            UserLoggedIn::dispatch($user, $this->isFirstLogin($user));

            // Log audit event
            $this->auditService->log('user_logged_in_via_google', $user->id, [
                'provider' => 'google',
                'google_id' => $googleUser->getId(),
            ]);

            // Redirect to dashboard or profile setup
            if ($user->staff && $user->staff->isProfileComplete()) {
                return redirect()->route('dashboard');
            } else {
                return redirect()->route('profile.edit');
            }

        } catch (\Exception $e) {
            // Log the error
            \Log::error('Google OAuth error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->route('login')->withErrors([
                'email' => 'Unable to authenticate with Google. Please try again.',
            ]);
        }
    }

    /**
     * Check if this is the user's first login.
     */
    private function isFirstLogin(User $user): bool
    {
        return $user->created_at->diffInMinutes(now()) < 5;
    }
}