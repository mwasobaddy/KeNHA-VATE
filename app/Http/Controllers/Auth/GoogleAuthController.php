<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Events\UserLoggedIn;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
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
        // This ensures the originally requested URL is preserved throughout the authentication flow
        if (!Session::has('url.intended') && Session::has('url.intended')) {
            // Keep existing intended URL
        } else if (request()->has('intended')) {
            Session::put('url.intended', request('intended'));
        } else if (request()->headers->has('referer')) {
            // Fallback: store referer if no intended URL
            $referer = request()->headers->get('referer');
            if ($referer && !str_contains($referer, route('login'))) {
                Session::put('url.intended', $referer);
            }
        }

        try {
            $googleUser = Socialite::driver('google')->user();

            // Split Google name into first and other names
            $nameParts = explode(' ', $googleUser->getName(), 2);
            $firstName = $nameParts[0] ?? '';
            $otherNames = $nameParts[1] ?? '';

            // Check if user exists with this email
            $user = User::where('email', $googleUser->getEmail())->first();

            if ($user) {
                // make the terms_accepted false upon every new login
                $user->terms_accepted = false;
                $user->save();

                // Only update user data if profile is not complete
                if (!$this->isProfileComplete($user)) {
                    // Merge existing user with Google data
                    $user->update([
                        'google_id' => $googleUser->getId(),
                        'username' => $googleUser->getName(),
                        'first_name' => $nameParts[0] ?? '',
                        'other_names' => $nameParts[1] ?? '',
                        'email_verified_at' => now(), // Google emails are verified
                    ]);
                } else {
                    // Profile is complete, just ensure Google ID is set
                    if (empty($user->google_id)) {
                        $user->update(['google_id' => $googleUser->getId()]);
                    }
                }
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
                
                // Assign the 'user' role to new users
                $user->assignRole('user');
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

            // Store session version for validity checking
            session(['session_version' => $user->session_version]);

            // Fire login event
            UserLoggedIn::dispatch($user, $this->isFirstLogin($user));

            // Log audit event (also reference the user resource)
            $this->auditService->log(
                'user_logged_in_via_google',
                $user->id,
                [
                    'provider' => 'google',
                    'google_id' => $googleUser->getId(),
                ],
                null, // request
                App\Models\User::class, // resource_type user model
                $user->id // resource_id
            );

            // Redirect based on profile completion status
            if (!$this->isProfileComplete($user)) {
                return redirect()->route('profile.edit');
            }

            // Check if terms accepted
            if (!$user->hasAcceptedTerms()) {
                return redirect()->route('terms.show');
            }
            
            // redirect intended
            return redirect()->intended('dashboard');

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

    /**
     * Check if user's profile is complete based on their category.
     */
    private function isProfileComplete(User $user): bool
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