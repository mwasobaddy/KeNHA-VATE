<?php

use App\Events\UserLoggedIn;
use App\Models\User;
use App\Services\AuthenticationService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.auth')] class extends Component {
    public string $email = '';
    public string $otp = '';
    public bool $remember = false;
    public int $remainingAttempts = 5;

    /**
     * Mount the component with email from session/route.
     */
    public function mount(): void
    {
        $this->email = session('otp_email', '');
        if (empty($this->email)) {
            $this->redirect(route('login'), navigate: true);
        }
    }

    /**
     * Verify OTP and log in user.
     */
    public function verifyOtp(): void
    {
        $this->validate([
            'otp' => 'required|string|digits:6',
        ]);

        $authService = app(AuthenticationService::class);
        $isValid = $authService->verifyOtp($this->email, $this->otp);

        if (!$isValid) {
            $this->remainingAttempts--;

            if ($this->remainingAttempts <= 0) {
                RateLimiter::hit($this->throttleKey(), 900); // 15 minutes lockout
                $this->addError('otp', 'Too many failed attempts. Please try again later.');
                return;
            }

            $this->addError('otp', "Invalid OTP. {$this->remainingAttempts} attempts remaining.");
            return;
        }

        // OTP is valid, find or create user
        $user = User::where('email', $this->email)->first();

        if (!$user) {
            // New user - create account
            $user = User::create([
                'email' => $this->email,
                'account_status' => 'active',
                'points' => 0,
            ]);
        }

        // Log in the user
        Auth::login($user, $this->remember);
        Session::regenerate();

        // Clear rate limiter
        RateLimiter::clear($this->throttleKey());

        // Fire login event for gamification
        $isFirstLogin = !$user->wasRecentlyCreated && $user->created_at->diffInMinutes(now()) < 5;
        UserLoggedIn::dispatch($user, $isFirstLogin);

        // Check if profile is complete
        if (!$user->staff || !$user->staff->isProfileComplete()) {
            $this->redirect(route('profile.setup'), navigate: true);
            return;
        }

        // Check if terms accepted
        if (!$user->hasAcceptedTerms()) {
            $this->redirect(route('terms.show'), navigate: true);
            return;
        }

        // Redirect to dashboard
        $this->redirectIntended(default: route('dashboard', absolute: false), navigate: true);
    }

    /**
     * Resend OTP to the email.
     */
    public function resendOtp(): void
    {
        $this->ensureIsNotRateLimited();

        $authService = app(AuthenticationService::class);
        $success = $authService->sendOtp($this->email);

        if ($success) {
            $this->dispatch('otp-resent', message: 'OTP resent to your email address.');
        } else {
            $this->addError('otp', 'Unable to resend OTP. Please try again.');
        }
    }

    /**
     * Go back to login (email form).
     */
    public function backToLogin(): void
    {
        $this->redirect(route('login'), navigate: true);
    }

    /**
     * Ensure the request is not rate limited.
     */
    protected function ensureIsNotRateLimited(): void
    {
        if (!RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'otp' => __('Too many attempts. Please try again in :minutes minutes.', [
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the rate limiting throttle key.
     */
    protected function throttleKey(): string
    {
        return 'otp:' . Str::lower($this->email) . '|' . request()->ip();
    }
}; ?>

<div class="flex flex-col gap-6">
    <x-auth-header
        :title="__('Verify your email')"
        :description="__('Enter the 6-digit code sent to your email')"
    />

    <!-- Session Status -->
    <x-auth-session-status class="text-center" :status="session('status')" />

    <!-- OTP Form -->
    <form method="POST" wire:submit="verifyOtp" class="flex flex-col gap-6">
        <div class="text-center">
            <p class="text-sm text-zinc-600 dark:text-zinc-400 mb-4">
                {{ __('We sent a 6-digit code to') }} <strong>{{ $email }}</strong>
            </p>
        </div>

        <!-- OTP Input -->
        <flux:input
            wire:model="otp"
            :label="__('Enter 6-digit code')"
            type="text"
            required
            autofocus
            maxlength="6"
            placeholder="000000"
            class="text-center text-2xl tracking-widest"
        />

        <!-- Remember Me -->
        <flux:checkbox wire:model="remember" :label="__('Remember me')" />

        <div class="text-sm text-zinc-600 dark:text-zinc-400 text-center">
            {{ __('Didn\'t receive the code?') }}
            <button
                type="button"
                wire:click="resendOtp"
                class="text-blue-600 hover:text-blue-500 font-medium"
            >
                {{ __('Resend OTP') }}
            </button>
        </div>

        <div class="flex items-center justify-end gap-3">
            <flux:button
                variant="ghost"
                type="button"
                wire:click="backToLogin"
            >
                {{ __('Back') }}
            </flux:button>

            <flux:button variant="primary" type="submit" class="flex-1">
                {{ __('Verify & Login') }}
            </flux:button>
        </div>
    </form>
</div>