<?php

use Livewire\Volt\Component;

new class extends Component {
    //
}; ?>

<section class="w-full">
    @include('partials.settings-heading')

    <x-settings.layout :heading="__('Appearance')" :subheading="__('Update the appearance settings for your account')">

        {{-- Appearance Settings Card --}}
        <div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 shadow-sm overflow-hidden transition-all duration-200 hover:shadow-md">
            <div class="bg-gradient-to-r from-zinc-50 to-white dark:from-zinc-800 dark:to-zinc-800/50 px-6 py-4 border-b border-zinc-200 dark:border-zinc-700">
                <div class="flex items-center gap-3">
                    <div class="flex-shrink-0 w-12 h-12 bg-[#FFF200] dark:bg-yellow-400 rounded-lg flex items-center justify-center">
                        <flux:icon name="eye" class="w-5 h-5 text-white" />
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">Theme Preferences</h3>
                        <p class="text-sm text-zinc-600 dark:text-zinc-400">Choose how KeNHAVATE looks for you</p>
                    </div>
                </div>
            </div>

            <div class="p-6">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-3">
                            Appearance Mode
                        </label>
                        <flux:radio.group x-data variant="segmented" x-model="$flux.appearance" class="w-full">
                            <flux:radio value="light" icon="sun" class="flex-1">Light</flux:radio>
                            <flux:radio value="dark" icon="moon" class="flex-1">Dark</flux:radio>
                            <flux:radio value="system" icon="computer-desktop" class="flex-1">System</flux:radio>
                        </flux:radio.group>
                    </div>

                    <div class="mb-8 bg-gradient-to-r from-[#F8EBD5] to-white dark:from-zinc-800 dark:to-zinc-800/50 rounded-xl border border-[#FFF200] dark:border-yellow-400 p-6 shadow-sm">
                        <div class="flex items-start gap-3">
                            <div class="flex-shrink-0 w-12 h-12 bg-[#FFF200] dark:bg-yellow-400 rounded-full flex items-center justify-center">
                                <flux:icon name="information-circle" class="w-5 h-5 text-[#231F20] dark:text-zinc-900 mt-0.5 flex-shrink-0" />
                            </div>
                            <div class="text-sm">
                                <p class="font-medium text-blue-900 dark:text-blue-100 mb-1">Theme Information</p>
                                <ul class="text-blue-800 dark:text-blue-200 space-y-1">
                                    <li><strong>Light:</strong> Clean, bright interface perfect for well-lit environments</li>
                                    <li><strong>Dark:</strong> Easy on the eyes, ideal for low-light conditions</li>
                                    <li><strong>System:</strong> Automatically matches your device's theme preference</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

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

        /* Information box styling */
        .bg-gradient-to-r.from-blue-50 {
            background-size: 200% 200%;
            animation: gradient-shift 20s ease infinite;
        }

        /* Radio button enhancements */
        .flux-radio {
            transition: all 0.2s ease;
        }

        .flux-radio:hover {
            transform: translateY(-1px);
        }

        .flux-radio[aria-checked="true"] {
            box-shadow: 0 0 0 2px #3b82f6;
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

            // Theme preview functionality
            const radios = document.querySelectorAll('[x-model="$flux.appearance"] [role="radio"]');

            radios.forEach(radio => {
                radio.addEventListener('mouseenter', function() {
                    const value = this.getAttribute('value');
                    // Add subtle preview effect
                    this.style.transform = 'scale(1.02)';
                });

                radio.addEventListener('mouseleave', function() {
                    this.style.transform = 'scale(1)';
                });
            });

            // Auto-save preference (could be enhanced with local storage)
            const radioGroup = document.querySelector('[x-model="$flux.appearance"]');

            if (radioGroup) {
                radioGroup.addEventListener('change', function() {
                    clearTimeout(saveTimeout);
                    saveTimeout = setTimeout(() => {
                        // Could dispatch a save event here
                        console.log('Theme preference updated');
                    }, 500);
                });
            }

            // Accessibility enhancement for keyboard navigation
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    const focusedElement = document.activeElement;
                    if (focusedElement && focusedElement.closest('[x-model="$flux.appearance"]')) {
                        e.preventDefault();
                        focusedElement.click();
                    }
                }
            });

            // Theme transition effect
            window.addEventListener('livewire:init', () => {
                Livewire.on('appearance-updated', () => {
                    // Smooth transition effect when theme changes
                    document.body.style.transition = 'background-color 0.3s ease, color 0.3s ease';
                    setTimeout(() => {
                        document.body.style.transition = '';
                    }, 300);
                });
            });

            // Detect system theme changes
            window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
                if (document.querySelector('[value="system"][aria-checked="true"]')) {
                    // System theme is selected, trigger update
                    console.log('System theme changed to:', e.matches ? 'dark' : 'light');
                }
            });
        });
    </script>
</section>
