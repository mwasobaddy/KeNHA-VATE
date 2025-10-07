<?php

use Laravel\Fortify\Actions\ConfirmTwoFactorAuthentication;
use Laravel\Fortify\Actions\DisableTwoFactorAuthentication;
use Laravel\Fortify\Actions\EnableTwoFactorAuthentication;
use Laravel\Fortify\Features;
use Laravel\Fortify\Fortify;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Validate;
use Livewire\Volt\Component;
use Symfony\Component\HttpFoundation\Response;

new class extends Component {
    #[Locked]
    public bool $twoFactorEnabled;

    #[Locked]
    public bool $requiresConfirmation;

    #[Locked]
    public string $qrCodeSvg = '';

    #[Locked]
    public string $manualSetupKey = '';

    public bool $showModal = false;

    public bool $showVerificationStep = false;

    #[Validate('required|string|size:6', onUpdate: false)]
    public string $code = '';

    /**
     * Mount the component.
     */
    public function mount(DisableTwoFactorAuthentication $disableTwoFactorAuthentication): void
    {
        abort_unless(Features::enabled(Features::twoFactorAuthentication()), Response::HTTP_FORBIDDEN);

        if (Fortify::confirmsTwoFactorAuthentication() && is_null(auth()->user()->two_factor_confirmed_at)) {
            $disableTwoFactorAuthentication(auth()->user());
        }

        $this->twoFactorEnabled = auth()->user()->hasEnabledTwoFactorAuthentication();
        $this->requiresConfirmation = Features::optionEnabled(Features::twoFactorAuthentication(), 'confirm');
    }

    /**
     * Enable two-factor authentication for the user.
     */
    public function enable(EnableTwoFactorAuthentication $enableTwoFactorAuthentication): void
    {
        $enableTwoFactorAuthentication(auth()->user());

        if (! $this->requiresConfirmation) {
            $this->twoFactorEnabled = auth()->user()->hasEnabledTwoFactorAuthentication();
        }

        $this->loadSetupData();

        $this->showModal = true;
    }

    /**
     * Load the two-factor authentication setup data for the user.
     */
    private function loadSetupData(): void
    {
        $user = auth()->user();

        try {
            $this->qrCodeSvg = $user?->twoFactorQrCodeSvg();
            $this->manualSetupKey = decrypt($user->two_factor_secret);
        } catch (Exception) {
            $this->addError('setupData', 'Failed to fetch setup data.');

            $this->reset('qrCodeSvg', 'manualSetupKey');
        }
    }

    /**
     * Show the two-factor verification step if necessary.
     */
    public function showVerificationIfNecessary(): void
    {
        if ($this->requiresConfirmation) {
            $this->showVerificationStep = true;

            $this->resetErrorBag();

            return;
        }

        $this->closeModal();
    }

    /**
     * Confirm two-factor authentication for the user.
     */
    public function confirmTwoFactor(ConfirmTwoFactorAuthentication $confirmTwoFactorAuthentication): void
    {
        $this->validate();

        $confirmTwoFactorAuthentication(auth()->user(), $this->code);

        $this->closeModal();

        $this->twoFactorEnabled = true;
    }

    /**
     * Reset two-factor verification state.
     */
    public function resetVerification(): void
    {
        $this->reset('code', 'showVerificationStep');

        $this->resetErrorBag();
    }

    /**
     * Disable two-factor authentication for the user.
     */
    public function disable(DisableTwoFactorAuthentication $disableTwoFactorAuthentication): void
    {
        $disableTwoFactorAuthentication(auth()->user());

        $this->twoFactorEnabled = false;
    }

    /**
     * Close the two-factor authentication modal.
     */
    public function closeModal(): void
    {
        $this->reset(
            'code',
            'manualSetupKey',
            'qrCodeSvg',
            'showModal',
            'showVerificationStep',
        );

        $this->resetErrorBag();

        if (! $this->requiresConfirmation) {
            $this->twoFactorEnabled = auth()->user()->hasEnabledTwoFactorAuthentication();
        }
    }

    /**
     * Get the current modal configuration state.
     */
    public function getModalConfigProperty(): array
    {
        if ($this->twoFactorEnabled) {
            return [
                'title' => 'Two-Factor Authentication Enabled',
                'description' => 'Two-factor authentication is now enabled. Scan the QR code or enter the setup key in your authenticator app.',
                'buttonText' => 'Close',
            ];
        }

        if ($this->showVerificationStep) {
            return [
                'title' => 'Verify Authentication Code',
                'description' => 'Enter the 6-digit code from your authenticator app.',
                'buttonText' => 'Continue',
            ];
        }

        return [
            'title' => 'Enable Two-Factor Authentication',
            'description' => 'To finish enabling two-factor authentication, scan the QR code or enter the setup key in your authenticator app.',
            'buttonText' => 'Continue',
        ];
    }
} ?>

<section class="w-full">
    @include('partials.settings-heading')

    <x-settings.layout
        :heading="__('Two Factor Authentication')"
        :subheading="__('Manage your two-factor authentication settings')"
    >

        {{-- Two-Factor Authentication Status Card --}}
        <div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 shadow-sm overflow-hidden transition-all duration-200 hover:shadow-md">
            <div class="bg-gradient-to-r from-zinc-50 to-white dark:from-zinc-800 dark:to-zinc-800/50 px-6 py-4 border-b border-zinc-200 dark:border-zinc-700">
                <div class="flex items-center gap-3">
                    <div class="flex-shrink-0 w-12 h-12 bg-[#FFF200] dark:bg-yellow-400 rounded-lg flex items-center justify-center">
                        <flux:icon name="shield-check" class="w-5 h-5 text-[#231F20] dark:text-zinc-900" />
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">Security Status</h3>
                        <p class="text-sm text-zinc-600 dark:text-zinc-400">Two-factor authentication settings</p>
                    </div>
                </div>
            </div>

            <div class="p-6">
                <div class="flex flex-col w-full mx-auto space-y-6 text-sm" wire:cloak>
                    @if ($twoFactorEnabled)
                        <div class="space-y-4">
                            <div class="flex items-center gap-3">
                                <flux:badge color="green">Enabled</flux:badge>
                                <span class="text-sm text-zinc-600 dark:text-zinc-400">Your account is protected with 2FA</span>
                            </div>

                            <div class="p-4 bg-green-50 dark:bg-green-900/20 rounded-lg border border-green-200 dark:border-green-800">
                                <div class="flex items-start gap-3">
                                    <flux:icon name="check-circle" class="w-5 h-5 text-green-600 dark:text-green-400 mt-0.5 flex-shrink-0" />
                                    <div class="text-sm">
                                        <p class="font-medium text-green-900 dark:text-green-100 mb-1">Enhanced Security Active</p>
                                        <p class="text-green-800 dark:text-green-200">With two-factor authentication enabled, you will be prompted for a secure, random pin during login, which you can retrieve from the TOTP-supported application on your phone.</p>
                                    </div>
                                </div>
                            </div>

                            <livewire:settings.two-factor.recovery-codes :$requiresConfirmation/>

                            <div class="flex justify-start pt-4 border-t border-zinc-200 dark:border-zinc-700">
                                <flux:button
                                    variant="danger"
                                    icon="shield-exclamation"
                                    icon:variant="outline"
                                    wire:click="disable"
                                    class="px-4 py-2"
                                >
                                    Disable 2FA
                                </flux:button>
                            </div>
                        </div>
                    @else
                        <div class="space-y-4">
                            <div class="flex items-center gap-3">
                                <flux:badge color="red">Disabled</flux:badge>
                                <span class="text-sm text-zinc-600 dark:text-zinc-400">Your account needs additional protection</span>
                            </div>

                            <div class="p-4 bg-amber-50 dark:bg-amber-900/20 rounded-lg border border-amber-200 dark:border-amber-800">
                                <div class="flex items-start gap-3">
                                    <flux:icon name="exclamation-triangle" class="w-5 h-5 text-amber-600 dark:text-amber-400 mt-0.5 flex-shrink-0" />
                                    <div class="text-sm">
                                        <p class="font-medium text-amber-900 dark:text-amber-100 mb-1">Security Recommendation</p>
                                        <p class="text-amber-800 dark:text-amber-200">When you enable two-factor authentication, you will be prompted for a secure pin during login. This pin can be retrieved from a TOTP-supported application on your phone.</p>
                                    </div>
                                </div>
                            </div>

                            <div class="flex justify-start pt-4 border-t border-zinc-200 dark:border-zinc-700">
                                <flux:button
                                    variant="primary"
                                    icon="shield-check"
                                    icon:variant="outline"
                                    wire:click="enable"
                                    class="px-4 py-2 bg-[#FFF200] dark:bg-yellow-400 text-[#231F20] dark:text-zinc-900 hover:bg-[#FFF200]/90 dark:hover:bg-yellow-300"
                                >
                                    Enable 2FA
                                </flux:button>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

    </x-settings.layout>

    <flux:modal
        name="two-factor-setup-modal"
        class="max-w-md md:min-w-md"
        @close="closeModal"
        wire:model="showModal"
    >
        <div class="space-y-6">
            <div class="flex flex-col items-center space-y-4">
                <div class="p-0.5 w-auto rounded-full border border-stone-100 dark:border-stone-600 bg-white dark:bg-stone-800 shadow-sm">
                    <div class="p-2.5 rounded-full border border-stone-200 dark:border-stone-600 overflow-hidden bg-stone-100 dark:bg-stone-200 relative">
                        <div class="flex items-stretch absolute inset-0 w-full h-full divide-x [&>div]:flex-1 divide-stone-200 dark:divide-stone-300 justify-around opacity-50">
                            @for ($i = 1; $i <= 5; $i++)
                                <div></div>
                            @endfor
                        </div>

                        <div class="flex flex-col items-stretch absolute w-full h-full divide-y [&>div]:flex-1 inset-0 divide-stone-200 dark:divide-stone-300 justify-around opacity-50">
                            @for ($i = 1; $i <= 5; $i++)
                                <div></div>
                            @endfor
                        </div>

                        <flux:icon.qr-code class="relative z-20 dark:text-accent-foreground"/>
                    </div>
                </div>

                <div class="space-y-2 text-center">
                    <flux:heading size="lg">{{ $this->modalConfig['title'] }}</flux:heading>
                    <flux:text>{{ $this->modalConfig['description'] }}</flux:text>
                </div>
            </div>

            @if ($showVerificationStep)
                <div class="space-y-6">
                    <div class="flex flex-col items-center space-y-3">
                        <x-input-otp
                            :digits="6"
                            name="code"
                            wire:model="code"
                            autocomplete="one-time-code"
                        />
                        @error('code')
                            <flux:text color="red">
                                {{ $message }}
                            </flux:text>
                        @enderror
                    </div>

                    <div class="flex items-center space-x-3">
                        <flux:button
                            variant="outline"
                            class="flex-1"
                            wire:click="resetVerification"
                        >
                            Back
                        </flux:button>

                        <flux:button
                            variant="primary"
                            class="flex-1"
                            wire:click="confirmTwoFactor"
                            x-bind:disabled="$wire.code.length < 6"
                        >
                            Confirm
                        </flux:button>
                    </div>
                </div>
            @else
                @error('setupData')
                    <flux:callout variant="danger" icon="x-circle" heading="{{ $message }}"/>
                @enderror

                <div class="flex justify-center">
                    <div class="relative w-64 overflow-hidden border rounded-lg border-stone-200 dark:border-stone-700 aspect-square">
                        @empty($qrCodeSvg)
                            <div class="absolute inset-0 flex items-center justify-center bg-white dark:bg-stone-700 animate-pulse">
                                <flux:icon.loading/>
                            </div>
                        @else
                            <div class="flex items-center justify-center h-full p-4">
                                {!! $qrCodeSvg !!}
                            </div>
                        @endempty
                    </div>
                </div>

                <div>
                    <flux:button
                        :disabled="$errors->has('setupData')"
                        variant="primary"
                        class="w-full"
                        wire:click="showVerificationIfNecessary"
                    >
                        {{ $this->modalConfig['buttonText'] }}
                    </flux:button>
                </div>

                <div class="space-y-4">
                    <div class="relative flex items-center justify-center w-full">
                        <div class="absolute inset-0 w-full h-px top-1/2 bg-stone-200 dark:bg-stone-600"></div>
                        <span class="relative px-2 text-sm bg-white dark:bg-stone-800 text-stone-600 dark:text-stone-400">
                            or, enter the code manually
                        </span>
                    </div>

                    <div
                        class="flex items-center space-x-2"
                        x-data="{
                            copied: false,
                            async copy() {
                                try {
                                    await navigator.clipboard.writeText('{{ $manualSetupKey }}');
                                    this.copied = true;
                                    setTimeout(() => this.copied = false, 1500);
                                } catch (e) {
                                    console.warn('Could not copy to clipboard');
                                }
                            }
                        }"
                    >
                        <div class="flex items-stretch w-full border rounded-xl dark:border-stone-700">
                            @empty($manualSetupKey)
                                <div class="flex items-center justify-center w-full p-3 bg-stone-100 dark:bg-stone-700">
                                    <flux:icon.loading variant="mini"/>
                                </div>
                            @else
                                <input
                                    type="text"
                                    readonly
                                    value="{{ $manualSetupKey }}"
                                    class="w-full p-3 bg-transparent outline-none text-stone-900 dark:text-stone-100"
                                />

                                <button
                                    @click="copy()"
                                    class="px-3 transition-colors border-l cursor-pointer border-stone-200 dark:border-stone-600"
                                >
                                    <flux:icon.document-duplicate x-show="!copied" variant="outline"></flux:icon>
                                    <flux:icon.check
                                        x-show="copied"
                                        variant="solid"
                                        class="text-green-500"
                                    ></flux:icon>
                                </button>
                            @endempty
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </flux:modal>

    {{-- Custom Styles for Enhanced UI --}}
    <style>
        /* Smooth transitions for all interactive elements */
        input, select, textarea {
            transition: all 0.2s ease-in-out;
        }

        /* Enhanced focus states */
        input:focus, select:focus, textarea:focus {
            transform: translateY(-1px);
        }

        /* Card hover effects */
        .bg-white:hover, .dark .dark\:bg-zinc-800:hover {
            transform: translateY(-2px);
        }

        /* Button press effect */
        button:active {
            transform: scale(0.98);
        }

        /* Smooth scroll */
        html {
            scroll-behavior: smooth;
        }

        /* Custom select arrow */
        select {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e");
            background-position: right 0.5rem center;
            background-repeat: no-repeat;
            background-size: 1.5em 1.5em;
            padding-right: 2.5rem;
        }

        /* Animated gradient background for cards */
        @keyframes gradient-shift {
            0%, 100% {
                background-position: 0% 50%;
            }
            50% {
                background-position: 100% 50%;
            }
        }

        .bg-gradient-to-r {
            background-size: 200% 200%;
            animation: gradient-shift 15s ease infinite;
        }

        /* Loading state animation */
        @keyframes pulse {
            0%, 100% {
                opacity: 1;
            }
            50% {
                opacity: 0.5;
            }
        }

        [wire\:loading] {
            animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }

        /* Enhanced tooltip styling */
        [title] {
            position: relative;
            cursor: help;
        }

        /* Success message animation */
        @keyframes slideInRight {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        x-action-message > div {
            animation: slideInRight 0.3s ease-out;
        }

        /* Enhanced error message styling */
        .text-red-600, .dark .dark\:text-red-400 {
            font-weight: 500;
        }

        /* Responsive font scaling */
        @media (max-width: 640px) {
            h3 {
                font-size: 1rem;
            }

            p {
                font-size: 0.875rem;
            }
        }

        /* Dark mode specific enhancements */
        @media (prefers-color-scheme: dark) {
            input:focus, select:focus, textarea:focus {
                box-shadow: 0 0 0 3px rgba(255, 242, 0, 0.1);
            }
        }

        /* Print styles */
        @media print {
            .no-print {
                display: none !important;
            }

            .bg-white {
                box-shadow: none !important;
                border: 1px solid #e5e7eb !important;
            }
        }

        /* Accessibility improvements */
        *:focus-visible {
            outline: 2px solid #FFF200;
            outline-offset: 2px;
        }

        /* Reduced motion for users who prefer it */
        @media (prefers-reduced-motion: reduce) {
            *,
            *::before,
            *::after {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
            }
        }

        /* Enhanced placeholder styling */
        ::placeholder {
            color: #9ca3af;
            opacity: 0.6;
        }

        .dark ::placeholder {
            color: #6b7280;
            opacity: 0.5;
        }

        /* Input group styling for better visual hierarchy */
        .space-y-2 > p {
            margin-top: 0.25rem;
        }

        /* Enhanced optgroup styling */
        optgroup {
            font-weight: 600;
            color: #374151;
        }

        .dark optgroup {
            color: #d1d5db;
        }

        option {
            padding: 0.5rem;
        }

        /* Card shadow on focus-within */
        .bg-white:focus-within, .dark .dark\:bg-zinc-800:focus-within {
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }

        /* Icon animations */
        svg {
            transition: transform 0.2s ease;
        }

        .group:hover svg {
            transform: scale(1.1);
        }

        /* Badge styling for required fields */
        label:has(+ input[required])::after,
        label:has(+ select[required])::after {
            content: " *";
            color: #ef4444;
            font-weight: bold;
        }

        /* Enhanced divider styling */
        .border-t {
            position: relative;
        }

        .border-t::before {
            content: "";
            position: absolute;
            top: -1px;
            left: 0;
            right: 0;
            height: 1px;
            background: linear-gradient(90deg, transparent, #e5e7eb 50%, transparent);
        }

        .dark .border-t::before {
            background: linear-gradient(90deg, transparent, #3f3f46 50%, transparent);
        }

        /* Information box styling */
        .bg-gradient-to-r.from-green-50,
        .bg-gradient-to-r.from-amber-50 {
            background-size: 200% 200%;
            animation: gradient-shift 20s ease infinite;
        }

        /* Modal enhancements */
        .flux-modal {
            transition: all 0.3s ease;
        }

        /* QR code styling */
        .border-stone-200.dark\:border-stone-600 {
            transition: all 0.2s ease;
        }

        .border-stone-200:hover.dark\:border-stone-600:hover {
            transform: scale(1.02);
        }
    </style>

    {{-- JavaScript for enhanced interactivity --}}
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Auto-save draft functionality (optional)
            let saveTimeout;
            const formInputs = document.querySelectorAll('input, select, textarea');

            formInputs.forEach(input => {
                input.addEventListener('input', function() {
                    clearTimeout(saveTimeout);
                    saveTimeout = setTimeout(() => {
                        // Could implement auto-save here
                        console.log('Input changed:', input.name);
                    }, 2000);
                });
            });

            // Phone number formatting
            const phoneInput = document.querySelector('input[type="tel"]');
            if (phoneInput) {
                phoneInput.addEventListener('input', function(e) {
                    // Phone formatting logic could go here
                    console.log('Phone input changed');
                });
            }

            // Form validation feedback
            const form = document.querySelector('form');
            if (form) {
                form.addEventListener('submit', function() {
                    // Form validation feedback
                    console.log('Form submitted');
                });
            }

            // Smooth scroll to first error
            window.addEventListener('livewire:init', () => {
                Livewire.on('validation-error', () => {
                    const firstError = document.querySelector('.text-red-600, .dark\\:text-red-400');
                    if (firstError) {
                        firstError.scrollIntoView({
                            behavior: 'smooth',
                            block: 'center'
                        });
                    }
                }, 100);
            });

            // Character counter for text inputs
            const textInputs = document.querySelectorAll('input[type="text"], input[type="email"]');
            textInputs.forEach(input => {
                const maxLength = input.getAttribute('maxlength');
                if (maxLength) {
                    const counter = document.createElement('div');
                    counter.className = 'text-xs text-gray-500 mt-1';
                    input.parentNode.appendChild(counter);

                    input.addEventListener('input', function() {
                        const remaining = maxLength - this.value.length;
                        counter.textContent = `${remaining} characters remaining`;
                        if (remaining < 10) {
                            counter.className = 'text-xs text-red-500 mt-1';
                        } else {
                            counter.className = 'text-xs text-gray-500 mt-1';
                        }
                    });
                }
            });

            // Security status indicator animation
            const statusBadges = document.querySelectorAll('.flux-badge');
            statusBadges.forEach(badge => {
                badge.addEventListener('mouseenter', function() {
                    this.style.transform = 'scale(1.05)';
                });

                badge.addEventListener('mouseleave', function() {
                    this.style.transform = 'scale(1)';
                });
            });

            // Modal interaction enhancements
            const modal = document.querySelector('[x-data*="showModal"]');
            if (modal) {
                // Add entrance animation
                modal.addEventListener('show', function() {
                    this.style.animation = 'fadeIn 0.3s ease-out';
                });
            }

            // QR code hover effect
            const qrContainer = document.querySelector('.border-stone-200');
            if (qrContainer) {
                qrContainer.addEventListener('mouseenter', function() {
                    this.style.transform = 'scale(1.02)';
                    this.style.boxShadow = '0 10px 25px -5px rgba(0, 0, 0, 0.1)';
                });

                qrContainer.addEventListener('mouseleave', function() {
                    this.style.transform = 'scale(1)';
                    this.style.boxShadow = '';
                });
            }

            // Copy functionality enhancement
            const copyButton = document.querySelector('[x-data*="copied"] button');
            if (copyButton) {
                copyButton.addEventListener('click', function() {
                    // Add visual feedback
                    this.style.transform = 'scale(0.95)';
                    setTimeout(() => {
                        this.style.transform = 'scale(1)';
                    }, 150);
                });
            }

            // Two-factor status monitoring
            window.addEventListener('livewire:init', () => {
                Livewire.on('two-factor-enabled', () => {
                    // Success animation for enabling 2FA
                    const card = document.querySelector('.bg-white.dark\\:bg-zinc-800');
                    if (card) {
                        card.style.animation = 'pulse 0.5s ease-in-out';
                        setTimeout(() => {
                            card.style.animation = '';
                        }, 500);
                    }
                });

                Livewire.on('two-factor-disabled', () => {
                    // Warning animation for disabling 2FA
                    const card = document.querySelector('.bg-white.dark\\:bg-zinc-800');
                    if (card) {
                        card.style.animation = 'shake 0.5s ease-in-out';
                        setTimeout(() => {
                            card.style.animation = '';
                        }, 500);
                    }
                });
            });

            // Add shake animation for warnings
            const style = document.createElement('style');
            style.textContent = `
                @keyframes shake {
                    0%, 100% { transform: translateX(0); }
                    10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
                    20%, 40%, 60%, 80% { transform: translateX(5px); }
                }
                @keyframes fadeIn {
                    from { opacity: 0; transform: scale(0.95); }
                    to { opacity: 1; transform: scale(1); }
                }
            `;
            document.head.appendChild(style);
        });
    </script>
</section>
