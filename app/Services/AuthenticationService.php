<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class AuthenticationService
{
    public function __construct(
        private readonly AuditService $auditService,
        private readonly NotificationService $notificationService
    ) {}

    /**
     * Send OTP to user's email address.
     */
    public function sendOtp(string $email): bool
    {
        try {
            // Check if user exists and is active
            $user = User::where('email', $email)->first();

            if ($user && !$user->isActive()) {
                Log::warning('Attempted OTP send to inactive user', ['email' => $email, 'status' => $user->account_status]);
                return false;
            }

            // Generate secure OTP
            $otp = $this->generateOtp();
            $hashedOtp = Hash::make($otp);

            // LOG THE OTP CODE FOR DEBUGGING (REMOVE IN PRODUCTION)
            Log::info('Generated OTP', ['email' => $email, 'otp' => $otp]);

            // Store OTP in cache with expiration
            $cacheKey = "otp:{$email}";
            Cache::put($cacheKey, $hashedOtp, now()->addMinutes(config('kenhavate.otp.expiry_minutes', 10)));

            // Log the attempt
            $this->auditService->log('otp_sent', $user?->id, ['email' => $email]);

            // Send OTP email
            Mail::to($email)->send(new \App\Mail\OtpMail($otp));

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send OTP', ['email' => $email, 'error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Verify OTP for the given email.
     */
    public function verifyOtp(string $email, string $otp): bool
    {
        $cacheKey = "otp:{$email}";
        $hashedOtp = Cache::get($cacheKey);

        if (!$hashedOtp) {
            Log::warning('OTP verification failed: no OTP found in cache', ['email' => $email]);
            return false;
        }

        if (!Hash::check($otp, $hashedOtp)) {
            Log::warning('OTP verification failed: invalid OTP', ['email' => $email]);
            return false;
        }

        // Clear the OTP from cache
        Cache::forget($cacheKey);

        // Log successful verification
        $user = User::where('email', $email)->first();
        $this->auditService->log('otp_verified', $user?->id, ['email' => $email]);

        return true;
    }

    /**
     * Generate a secure OTP.
     */
    private function generateOtp(): string
    {
        $length = config('kenhavate.otp.length', 6);
        return (string) random_int(10 ** ($length - 1), (10 ** $length) - 1);
    }

    /**
     * Handle user login after OTP verification.
     */
    public function handleLogin(User $user): void
    {
        // Log the login
        $this->auditService->log('user_logged_in', $user->id);

        // Award points for login (once per session)
        // This will be handled by an event listener
    }

    /**
     * Handle Google OAuth authentication.
     */
    public function handleGoogleAuth(array $googleUserData): User
    {
        // Check if user exists with this Google ID
        $user = User::where('google_id', $googleUserData['id'])->first();

        if ($user) {
            // Update user info if needed
            $user->update([
                'name' => $googleUserData['name'],
                'email' => $googleUserData['email'],
            ]);
        } else {
            // Check if user exists with this email
            $user = User::where('email', $googleUserData['email'])->first();

            if ($user) {
                // Link Google account to existing user
                $user->update(['google_id' => $googleUserData['id']]);
            } else {
                // Create new user
                $user = User::create([
                    'name' => $googleUserData['name'],
                    'email' => $googleUserData['email'],
                    'google_id' => $googleUserData['id'],
                    'account_status' => 'active',
                    'points' => 0,
                ]);
            }
        }

        $this->auditService->log('google_auth_success', $user->id, ['provider' => 'google']);

        return $user;
    }
}