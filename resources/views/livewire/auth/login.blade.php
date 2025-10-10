<?php

use App\Models\User;
use App\Services\AuthenticationService;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.auth')] class extends Component {
    public string $email = '';
    public bool $remember = false;

    protected $listeners = ['otpSent' => '$refresh'];

    /**
     * Send OTP to the provided email.
     */
    public function sendOtp(): void
    {
        $this->validate([
            'email' => 'required|string|email|max:255',
        ]);

        $this->ensureIsNotRateLimited();

        // Store the intended URL in session if not already set
        // This ensures the originally requested URL is preserved throughout the authentication flow
        if (Session::has('url.intended')) {
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

        $user = User::where('email', $this->email)->first();
        
        // make the terms_accepted false upon every new login
        if ($user) {
            $user->terms_accepted = false;
            $user->save();
        }

        // Check account status
        if ($user && !$user->isActive()) {
            if ($user->isBanned()) {
                Session::put('immediate_popup_notification', [
                    'type' => 'error',
                    'title' => 'Account Banned',
                    'message' => 'Your account has been banned. Please contact support for assistance.',
                    'duration' => 7000,
                ]);
                $this->redirect(route('account.banned'), navigate: true);
                return;
            }

            if ($user->isDisabled()) {
                Session::put('immediate_popup_notification', [
                    'type' => 'warning',
                    'title' => 'Account Disabled',
                    'message' => 'Your account has been disabled. Please contact support to re-enable it.',
                    'duration' => 7000,
                ]);
                $this->redirect(route('account.disabled'), navigate: true);
                return;
            }
        }

        // Send OTP using service
        $authService = app(AuthenticationService::class);
        $success = $authService->sendOtp($this->email);

        if ($success) {
            // Store email in session for verification component
            Session::put('otp_email', $this->email);
            // Store popup notification for display on the OTP verification page
            Session::put('immediate_popup_notification', [
                'type' => 'success',
                'title' => 'OTP Sent Successfully',
                'message' => 'A one-time password has been sent to your email address. Please check your inbox and enter the code to continue.',
                'duration' => 5000,
            ]);
            $this->redirect(route('otp.verify'), navigate: true);
        } else {
            Session::put('immediate_popup_notification', [
                'type' => 'error',
                'title' => 'Failed to Send OTP',
                'message' => 'Unable to send OTP. Please try again.',
                'duration' => 5000,
            ]);
            $this->redirect(route('login'), navigate: true);
        }
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
            'email' => __('Too many login attempts. Please try again in :minutes minutes.', [
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
        :title="__('Welcome Back!')"
        :description="__('Enter your email below. We’ll send you a One Time Password (OTP) to log in or create your account instantly—no password needed.')"
    />

    <!-- Session Status -->
    <x-auth-session-status class="text-center" :status="session('status')" />

    <!-- Email Form -->
    <form method="POST" wire:submit="sendOtp" class="flex flex-col gap-6">
        <!-- Email Address -->
        <flux:input
            wire:model="email"
            :label="__('Email address')"
            type="email"
            required
            autofocus
            autocomplete="email"
            placeholder="email@example.com"
        />

        <!-- Remember Me -->
        <flux:checkbox wire:model="remember" :label="__('Remember me')" />

        <div class="space-y-4">
            <flux:button variant="primary" type="submit" class="w-full justify-center rounded-lg bg-[#FFF200] dark:bg-yellow-400 px-4 py-3 text-sm font-semibold text-[#231F20] dark:text-zinc-900 shadow-lg hover:bg-[#FFF200]/90 dark:hover:bg-yellow-300 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#FFF200] dark:focus-visible:outline-yellow-400 transition-all duration-200 hover:shadow-xl">
                {{ __('Send Verification Code') }}
            </flux:button>
        </div>
    </form>

    <!-- Divider -->
    <div class="relative">
        <div class="absolute inset-0 flex items-center">
            <div class="w-full border-t border-gray-300 dark:border-gray-600"></div>
        </div>
        <div class="relative flex justify-center text-sm">
            <span class="px-2 bg-white dark:bg-gray-900 text-gray-500 dark:text-gray-400">Or continue with</span>
        </div>
    </div>

    <!-- Google Login -->
    <flux:button 
        variant="outline" 
        type="button" 
        class="w-full" 
        onclick="window.location.href='{{ route('auth.google') }}'"
    >
        <svg class="w-5 h-5 mr-2" viewBox="0 0 24 24">
            <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
            <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
            <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
            <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
        </svg>
        Continue with Google
    </flux:button>

    @if (Route::has('register'))
        <div class="space-x-1 text-sm text-center rtl:space-x-reverse text-zinc-600 dark:text-zinc-400">
            <span>{{ __('New to KENHAVATE?') }}</span>
            <flux:link :href="route('register')" wire:navigate>{{ __('Create account') }}</flux:link>
        </div>
    @endif
</div>