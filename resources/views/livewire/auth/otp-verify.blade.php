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
    public array $otp = ['', '', '', '', '', ''];
    public bool $remember = false;
    public int $remainingAttempts = 5;
    // Timestamp (unix) when resend becomes available
    public int $resendAvailableAt = 0;

    /**
     * Mount the component with email from session/route.
     */
    public function mount(): void
    {
        $this->email = session('otp_email', '');
        if (empty($this->email)) {
            $this->redirect(route('login'), navigate: true);
        }

        // Load resend timer from session if present
        $this->resendAvailableAt = session('otp_resend_expires', 0);
        
        // If cooldown has expired, reset to 0
        if ($this->resendAvailableAt < time()) {
            $this->resendAvailableAt = 0;
        }
    }

    /**
     * Verify OTP and log in user.
     */
    public function verifyOtp(): void
    {
        $otpString = implode('', $this->otp);
        
        $this->validate([
            'otp.*' => 'required|numeric|digits:1',
        ], [
            'otp.*.required' => 'Please enter all 6 digits.',
            'otp.*.numeric' => 'Only numbers are allowed.',
            'otp.*.digits' => 'Each box must contain one digit.',
        ]);

        if (strlen($otpString) !== 6) {
            $this->addError('otp', 'Please enter all 6 digits.');
            return;
        }

        $authService = app(AuthenticationService::class);
        $isValid = $authService->verifyOtp($this->email, $otpString);

        if (!$isValid) {
            $this->remainingAttempts--;

            if ($this->remainingAttempts <= 0) {
                RateLimiter::hit($this->throttleKey(), 900); // 15 minutes lockout
                $this->addError('otp', 'Too many failed attempts. Please try again later.');
                $this->otp = ['', '', '', '', '', ''];
                return;
            }

            $this->addError('otp', "Invalid OTP. {$this->remainingAttempts} attempts remaining.");
            $this->otp = ['', '', '', '', '', ''];
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
    
            // Assign the 'user' role to new users
            $user->assignRole('user');
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
        if (!$this->isProfileComplete($user)) {
            $this->redirect(route('profile.edit'), navigate: true);
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
        // Prevent resend if cooldown still active
        $now = now()->timestamp;
        $expires = session('otp_resend_expires', 0);
        if ($expires && $expires > $now) {
            $seconds = $expires - $now;
            $this->addError('otp', "Please wait {$seconds} seconds before resending.");
            return;
        }

        $this->ensureIsNotRateLimited();

        $authService = app(AuthenticationService::class);
        $success = $authService->sendOtp($this->email);

        if ($success) {
            // set resend cooldown for 59 seconds and persist in session
            $expiresAt = now()->addSeconds(59)->timestamp;
            session(['otp_resend_expires' => $expiresAt]);
            $this->resendAvailableAt = $expiresAt;

            $this->dispatch('showSuccess', 'OTP Resent', 'A new OTP has been sent to your email address.');
            $this->otp = ['', '', '', '', '', ''];
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
}; ?>

<div class="flex flex-col gap-8">
    <x-auth-header
        :title="__('Verify your email')"
        :description="__('Enter the 6-digit code sent to your email')"
    />

    <!-- Session Status -->
    <x-auth-session-status class="text-center" :status="session('status')" />

    <!-- OTP Form -->
    <form method="POST" wire:submit="verifyOtp" class="flex flex-col gap-8">
        <div class="rounded-lg bg-[#F8EBD5] dark:bg-zinc-800/50 p-4 border border-[#FFF200] dark:border-yellow-400">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-[#FFF200] dark:text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-[#231F20] dark:text-white">{{ __('Please enter the verification code sent to ":email"', ['email' => $email]) }}</p>
                </div>
            </div>
        </div>

        <div class="text-center space-y-2">
            <!-- Paste hint -->
            <div class="text-center mt-2">
                <p class="text-xs text-[#9B9EA4] dark:text-zinc-400">
                    💡 <span class="font-medium">Tip:</span> You can paste the entire OTP code at once into any field
                </p>
                <p class="text-xs text-[#9B9EA4] dark:text-zinc-400 mt-1">
                    Use <kbd class="px-1 py-0.5 bg-gray-200 dark:bg-gray-700 rounded text-xs">Ctrl+V</kbd> or <kbd class="px-1 py-0.5 bg-gray-200 dark:bg-gray-700 rounded text-xs">Cmd+V</kbd> to paste
                </p>
            </div>
        </div>

        <!-- OTP Input Boxes -->
        <div class="flex flex-col gap-4">
            <div class="flex justify-center gap-3" x-data="otpInput()">
                @for ($i = 0; $i < 6; $i++)
                    <input
                        type="text"
                        inputmode="numeric"
                        maxlength="1"
                        wire:model="otp.{{ $i }}"
                        x-ref="input{{ $i }}"
                        @input="handleInput($event, {{ $i }})"
                        @keydown="handleKeyDown($event, {{ $i }})"
                        @paste="handlePaste($event, {{ $i }})"
                        class="w-14 h-14 text-center text-lg font-bold rounded-full border-2 border-[#9B9EA4] dark:border-zinc-600 focus:border-[#FFF200] dark:focus:border-yellow-400 focus:ring-2 focus:ring-[#FFF200] dark:focus:ring-yellow-400 focus:ring-opacity-50 bg-white dark:bg-zinc-800/50 text-[#231F20] dark:text-white transition-all duration-200 hover:border-[#FFF200] dark:hover:border-yellow-400"
                        {{ $i === 0 ? 'autofocus' : '' }}
                    />
                @endfor
            </div>
            
            @error('otp')
                <p class="text-sm text-red-600 dark:text-red-400 text-center font-medium">
                    {{ $message }}
                </p>
            @enderror
        </div>

        <div class="space-y-4">
            <flux:button variant="primary" type="submit" class="w-full justify-center rounded-lg bg-[#FFF200] dark:bg-yellow-400 px-4 py-3 text-sm font-semibold text-[#231F20] dark:text-zinc-900 shadow-lg hover:bg-[#FFF200]/90 dark:hover:bg-yellow-300 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#FFF200] dark:focus-visible:outline-yellow-400 transition-all duration-200 hover:shadow-xl">
                {{ __('Verify & Continue') }}
            </flux:button>
        </div>

        <!-- Action Buttons -->
        <div class="flex flex-col-reverse sm:flex-row items-center gap-3">
            <flux:button
                variant="ghost"
                type="button"
                wire:click="backToLogin"
                class="w-full sm:w-auto border-2 border-[#9B9EA4] dark:border-zinc-600 text-sm text-[#231F20] dark:text-white hover:text-[#231F20] dark:hover:text-white/80 transition-colors duration-200"
            >
                {{ __('← Back to Login') }}
            </flux:button>

            <!-- Resend Link -->
            <div class="text-center" 
                 x-data="resendTimer({{ $resendAvailableAt }})"
                 @resend-complete.window="resetTimer()"
                 wire:ignore>
                <p class="text-sm text-zinc-600 dark:text-zinc-400">
                    {{ "Didn't receive the code?" }}
                    
                    <flux:button
                        x-show="!isActive"
                        type="button"
                        :loading="true"
                        variant="ghost"
                        @click="$wire.resendOtp().then(() => { startTimer(59); })"
                        class="mt-2 text-sm font-semibold text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300 transition-colors duration-200 underline underline-offset-2"
                    >
                        {{ 'Resend OTP' }}
                    </flux:button>

                    <span 
                        x-show="isActive" 
                        x-text="`Resend available in ${remaining}s`" 
                        class="mt-2 text-sm font-semibold text-zinc-600 dark:text-zinc-400">
                    </span>
                </p>
            </div>
        </div>
    </form>

    <script>
        function resendTimer(initialExpires) {
            return {
                remaining: 0,
                isActive: false,
                intervalId: null,

                init() {
                    // Calculate initial remaining time
                    if (initialExpires > 0) {
                        const now = Math.floor(Date.now() / 1000);
                        this.remaining = Math.max(0, initialExpires - now);
                        this.isActive = this.remaining > 0;
                        
                        if (this.isActive) {
                            this.startCountdown();
                        }
                    }
                },

                startTimer(seconds) {
                    this.remaining = seconds;
                    this.isActive = true;
                    this.startCountdown();
                },

                startCountdown() {
                    // Clear any existing interval
                    if (this.intervalId) {
                        clearInterval(this.intervalId);
                    }

                    // Start countdown
                    this.intervalId = setInterval(() => {
                        this.remaining--;
                        
                        if (this.remaining <= 0) {
                            this.resetTimer();
                        }
                    }, 1000);
                },

                resetTimer() {
                    this.isActive = false;
                    this.remaining = 0;
                    if (this.intervalId) {
                        clearInterval(this.intervalId);
                        this.intervalId = null;
                    }
                }
            }
        }

        function otpInput() {
            return {
                handleInput(event, index) {
                    const input = event.target;
                    const value = input.value;

                    // Only allow single digit
                    if (value.length > 1) {
                        input.value = value.charAt(0);
                        return;
                    }

                    // Only allow numbers
                    if (value && !/^\d$/.test(value)) {
                        input.value = '';
                        return;
                    }

                    // Move to next input if value entered
                    if (value && index < 5) {
                        this.$refs['input' + (index + 1)].focus();
                    }

                    // Auto-submit when all fields are filled
                    if (index === 5 && value) {
                        this.checkAutoSubmit();
                    }
                },

                handleKeyDown(event, index) {
                    const input = event.target;

                    // Handle backspace
                    if (event.key === 'Backspace') {
                        if (!input.value && index > 0) {
                            // Move to previous input if current is empty
                            this.$refs['input' + (index - 1)].focus();
                        } else {
                            // Clear current input
                            input.value = '';
                        }
                        event.preventDefault();
                    }

                    // Handle delete key
                    if (event.key === 'Delete') {
                        input.value = '';
                        event.preventDefault();
                    }

                    // Handle arrow keys
                    if (event.key === 'ArrowLeft' && index > 0) {
                        this.$refs['input' + (index - 1)].focus();
                        event.preventDefault();
                    }

                    if (event.key === 'ArrowRight' && index < 5) {
                        this.$refs['input' + (index + 1)].focus();
                        event.preventDefault();
                    }

                    // Handle home/end keys
                    if (event.key === 'Home') {
                        this.$refs['input0'].focus();
                        event.preventDefault();
                    }

                    if (event.key === 'End') {
                        this.$refs['input5'].focus();
                        event.preventDefault();
                    }
                },

                handlePaste(event, index) {
                    event.preventDefault();
                    const pasteData = event.clipboardData.getData('text').trim();
                    
                    // Only handle numeric paste data
                    if (!/^\d+$/.test(pasteData)) {
                        return;
                    }

                    // Get the digits starting from current index
                    const digits = pasteData.slice(0, 6 - index).split('');
                    
                    // Fill the inputs
                    digits.forEach((digit, i) => {
                        const targetIndex = index + i;
                        if (targetIndex < 6) {
                            const targetInput = this.$refs['input' + targetIndex];
                            targetInput.value = digit;
                            
                            // Trigger Livewire update
                            targetInput.dispatchEvent(new Event('input', { bubbles: true }));
                        }
                    });

                    // Focus the next empty input or the last input
                    const nextIndex = Math.min(index + digits.length, 5);
                    this.$refs['input' + nextIndex].focus();

                    // Auto-submit if all filled
                    if (nextIndex === 5 && this.$refs['input5'].value) {
                        this.checkAutoSubmit();
                    }
                },

                checkAutoSubmit() {
                    // Check if all inputs are filled
                    const allFilled = Array.from({ length: 6 }, (_, i) => {
                        return this.$refs['input' + i].value !== '';
                    }).every(Boolean);

                    if (allFilled) {
                        // Small delay to ensure Livewire has updated
                        setTimeout(() => {
                            this.$el.closest('form').dispatchEvent(new Event('submit', { bubbles: true, cancelable: true }));
                        }, 100);
                    }
                }
            }
        }
    </script>

    <style>
        /* Remove spinner from number inputs */
        input[type="text"]::-webkit-outer-spin-button,
        input[type="text"]::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }

        input[type="text"] {
            -moz-appearance: textfield;
        }
    </style>
</div>