<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Livewire\Volt\Component;

new class extends Component {
    public string $current_password = '';
    public string $password = '';
    public string $password_confirmation = '';

    /**
     * Update the password for the currently authenticated user.
     */
    public function updatePassword(): void
    {
        try {
            $validated = $this->validate([
                'current_password' => ['required', 'string', 'current_password'],
                'password' => ['required', 'string', Password::defaults(), 'confirmed'],
            ]);
        } catch (ValidationException $e) {
            $this->reset('current_password', 'password', 'password_confirmation');

            throw $e;
        }

        Auth::user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        $this->reset('current_password', 'password', 'password_confirmation');

        // Use popup notification instead of x-action-message
        $this->dispatch('showSuccess', 'Password Updated', 'Your password has been changed successfully.');
    }
}; ?>

<section class="w-full">
    @include('partials.settings-heading')

    <x-settings.layout :heading="__('Update Password')" :subheading="__('Ensure your account is using a long, random password to stay secure')">
        <form wire:submit="updatePassword" class="space-y-6">

            {{-- Password Update Card --}}
            <div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 shadow-sm overflow-hidden transition-all duration-200 hover:shadow-md">
                <div class="bg-gradient-to-r from-zinc-50 to-white dark:from-zinc-800 dark:to-zinc-800/50 px-6 py-4 border-b border-zinc-200 dark:border-zinc-700">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-[#FFF200] dark:bg-yellow-400 rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5 text-[#231F20] dark:text-zinc-900" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-[#231F20] dark:text-white">
                                {{ __('Password Security') }}
                            </h3>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400">
                                {{ __('Update your password to keep your account secure') }}
                            </p>
                        </div>
                    </div>
                </div>

                <div class="p-6 space-y-4">
                    <div class="space-y-2">
                        <flux:input
                            wire:model="current_password"
                            :label="__('Current Password')"
                            type="password"
                            required
                            autocomplete="current-password"
                            class="transition-all duration-200 focus:ring-2 focus:ring-[#FFF200] dark:focus:ring-yellow-400"
                        />
                        <p class="text-xs text-zinc-500 dark:text-zinc-400 flex items-center gap-1">
                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                            </svg>
                            {{ __('Enter your current password to verify your identity') }}
                        </p>
                    </div>

                    <div class="space-y-2">
                        <flux:input
                            wire:model="password"
                            :label="__('New Password')"
                            type="password"
                            required
                            autocomplete="new-password"
                            class="transition-all duration-200 focus:ring-2 focus:ring-[#FFF200] dark:focus:ring-yellow-400"
                        />
                        <p class="text-xs text-zinc-500 dark:text-zinc-400 flex items-center gap-1">
                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                            {{ __('Minimum 8 characters with mixed case, numbers, and symbols') }}
                        </p>
                    </div>

                    <div class="space-y-2">
                        <flux:input
                            wire:model="password_confirmation"
                            :label="__('Confirm New Password')"
                            type="password"
                            required
                            autocomplete="new-password"
                            class="transition-all duration-200 focus:ring-2 focus:ring-[#FFF200] dark:focus:ring-yellow-400"
                        />
                        <p class="text-xs text-zinc-500 dark:text-zinc-400 flex items-center gap-1">
                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                            {{ __('Re-enter your new password to confirm') }}
                        </p>
                    </div>
                </div>
            </div>

            {{-- Action Buttons --}}
            <div class="flex flex-col sm:flex-row items-center gap-4 pt-6 border-t border-zinc-200 dark:border-zinc-700">
                <div class="flex-1 w-full sm:w-auto">
                    <flux:button
                        variant="primary"
                        type="submit"
                        class="w-full sm:w-auto justify-center rounded-lg bg-[#FFF200] dark:bg-yellow-400 px-6 py-3 text-sm font-semibold text-[#231F20] dark:text-zinc-900 shadow-lg hover:bg-[#FFF200]/90 dark:hover:bg-yellow-300 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#FFF200] dark:focus-visible:outline-yellow-400 transition-all duration-200 hover:shadow-xl hover:scale-105"
                        data-test="update-password-button">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        {{ __('Update Password') }}
                    </flux:button>
                </div>
            </div>
        </form>
    </x-settings.layout>

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
    </style>

    {{-- JavaScript for enhanced interactivity --}}
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Form validation feedback
            const form = document.querySelector('form');
            if (form) {
                form.addEventListener('submit', function() {
                    const submitButton = form.querySelector('button[type="submit"]');
                    if (submitButton) {
                        submitButton.disabled = true;
                        submitButton.innerHTML = '<svg class="animate-spin h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Updating Password...';
                    }
                });
            }

            // Smooth scroll to first error
            window.addEventListener('livewire:init', () => {
                Livewire.on('validation-error', () => {
                    setTimeout(() => {
                        // Look for Flux error messages or any error text
                        const firstError = document.querySelector('.text-red-600, .text-red-400, [class*="error"], .invalid-feedback');
                        if (firstError) {
                            firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                            return;
                        }

                        // Fallback: look for any element with error styling
                        const errorElement = document.querySelector('[aria-invalid="true"], .border-red-500, .ring-red-500');
                        if (errorElement) {
                            errorElement.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        }
                    }, 100);
                });
            });

            // Password strength indicator (optional enhancement)
            const newPasswordInput = document.querySelector('input[name="password"]');
            if (newPasswordInput) {
                newPasswordInput.addEventListener('input', function() {
                    // Could add password strength indicator here
                    console.log('Password input changed');
                });
            }
        });
    </script>
</section>
