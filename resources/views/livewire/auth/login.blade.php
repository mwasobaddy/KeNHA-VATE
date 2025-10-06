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

        $user = User::where('email', $this->email)->first();

        // Check account status
        if ($user && !$user->isActive()) {
            if ($user->isBanned()) {
                $this->redirect(route('account.banned'), navigate: true);
                return;
            }

            if ($user->isDisabled()) {
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
            $this->dispatch('showError', 'Failed to Send OTP', 'Unable to send OTP. Please try again.');
            $this->addError('email', 'Unable to send OTP. Please try again.');
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
        :title="__('Log in to your account')"
        :description="__('Enter your email address to receive a one-time password')"
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

        <div class="flex items-center justify-end">
            <flux:button variant="primary" type="submit" class="w-full">
                {{ __('Send OTP') }}
            </flux:button>
        </div>
    </form>

    @if (Route::has('register'))
        <div class="space-x-1 text-sm text-center rtl:space-x-reverse text-zinc-600 dark:text-zinc-400">
            <span>{{ __('New to KENHAVATE?') }}</span>
            <flux:link :href="route('register')" wire:navigate>{{ __('Create account') }}</flux:link>
        </div>
    @endif
</div>