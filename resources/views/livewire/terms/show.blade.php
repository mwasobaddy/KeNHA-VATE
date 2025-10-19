<?php

use App\Services\UserService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app')] class extends Component {
    public bool $accepted = false;
    public bool $showTerms = false;

    /**
     * Accept the terms and conditions.
     */
    public function acceptTerms(): void
    {
        if (!$this->accepted) {
            Session::put('immediate_popup_notification', [
                'type' => 'warning',
                'title' => 'Terms Not Accepted',
                'message' => 'You must accept the terms and conditions to continue.',
                'duration' => 5000,
            ]);
            $this->redirect(route('terms.show'), navigate: true);
            return;
        }

        try {
            $user = Auth::user();
            $userService = app(UserService::class);

            // Accept terms with current version
            $userService->acceptTerms($user, '1.0');

            // Show success toast and redirect to intended URL or dashboard
            Session::put('immediate_popup_notification', [
                'type' => 'success',
                'title' => 'Terms Accepted',
                'message' => 'Welcome to KENHAVATE! You can now access all features.',
                'duration' => 5000,
            ]);
            $this->redirectIntended(default: route('dashboard', absolute: false), navigate: true);
        } catch (\Exception $e) {
            Session::put('immediate_popup_notification', [
                'type' => 'error',
                'title' => 'Acceptance Failed',
                'message' => 'There was an error accepting the terms. Please try again.',
                'duration' => 5000,
            ]);
            $this->redirect(route('terms.show'), navigate: true);
        }
    }

    /**
     * Decline the terms and conditions.
     */
    public function declineTerms(): void
    {
        Auth::logout();
        $this->redirect(route('login'), navigate: true);
    }
}; ?>

<div class="min-h-screen bg-gradient-to-br from-[#F8EBD5] via-white to-[#F8EBD5] dark:from-zinc-900 dark:via-zinc-800 dark:to-zinc-900 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-4xl mx-auto space-y-8">
        <!-- Header Section with Icon -->
        <div class="mb-8 sm:mb-12">
            <div class="flex flex-row items-start sm:items-center gap-4 sm:gap-6">
                <!-- Animated Icon Badge -->
                <div 
                    x-data="{ show: false }" 
                    x-init="setTimeout(() => show = true, 100)"
                    x-show="show"
                    x-transition:enter="transition ease-out duration-500 delay-100"
                    x-transition:enter-start="opacity-0 scale-75 -rotate-12"
                    x-transition:enter-end="opacity-100 scale-100 rotate-0"
                    class="flex-shrink-0"
                >
                    <div class="relative">
                        <div class="absolute inset-0 bg-[#FFF200]/20 dark:bg-yellow-400/20 rounded-2xl blur-xl"></div>
                        <div class="relative flex items-center justify-center w-16 h-16 sm:w-20 sm:h-20 rounded-2xl bg-gradient-to-br from-[#FFF200] via-yellow-300 to-yellow-400 dark:from-yellow-400 dark:via-yellow-500 dark:to-yellow-600 shadow-lg">
                            <flux:icon name="clipboard-document-list" class="w-8 h-8 sm:w-10 sm:h-10 text-[#231F20] dark:text-zinc-900" />
                        </div>
                    </div>
                </div>

                <!-- Header Text with staggered animation -->
                <div 
                    class="flex-1"
                    x-data="{ show: false }" 
                    x-init="setTimeout(() => show = true, 200)"
                >
                    <div 
                        x-show="show"
                        x-transition:enter="transition ease-out duration-700"
                        x-transition:enter-start="opacity-0 translate-x-4"
                        x-transition:enter-end="opacity-100 translate-x-0"
                    >
                        <h1 class="text-3xl sm:text-4xl lg:text-5xl font-bold text-[#231F20] dark:text-white tracking-tight">
                            {{ __('Terms & Conditions') }}
                        </h1>
                        <p class="mt-2 text-base sm:text-lg text-[#9B9EA4] dark:text-zinc-400">
                            {{ __('Please read and accept our terms to unlock the full KeNHAVATE experience') }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Progress Indicator -->
        <div class="flex items-center justify-center space-x-2 pb-4">
            <div class="flex items-center space-x-2">
                <div class="w-2 h-2 rounded-full bg-[#FFF200] dark:bg-yellow-400 animate-pulse"></div>
                <span class="text-sm font-medium text-[#231F20] dark:text-white">Step 1 of 1</span>
            </div>
        </div>

        <!-- Terms Content Card with Enhanced Styling -->
        <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-xl border-2 border-[#9B9EA4]/20 dark:border-zinc-700 overflow-hidden transition-all duration-300 hover:shadow-2xl">
            
            <!-- Accent Bar -->
            <div class="h-2 bg-gradient-to-r from-[#FFF200] via-yellow-300 to-[#FFF200] dark:from-yellow-400 dark:via-yellow-500 dark:to-yellow-400"></div>
            
            <!-- Scrollable Content Area -->
            <div class="p-8 max-h-[60vh] overflow-y-auto custom-scrollbar">
                <div class="prose prose-zinc dark:prose-invert max-w-none">
                    
                    <!-- Enhanced Section Headers -->
                    <div class="flex items-center space-x-3 mb-6">
                        <div class="w-8 h-8 rounded-lg bg-[#FFF200]/10 dark:bg-yellow-400/10 flex items-center justify-center">
                            <span class="text-lg font-bold text-[#FFF200] dark:text-yellow-400">1</span>
                        </div>
                        <h2 class="!mt-0 text-2xl font-bold text-[#231F20] dark:text-white">{{ __('Acceptance of Terms') }}</h2>
                    </div>
                    <p class="text-[#9B9EA4] dark:text-zinc-300 leading-relaxed">
                        {{ __('By accessing and using KENHAVATE, you accept and agree to be bound by the terms and provision of this agreement. This platform is designed to foster innovation within Kenya\'s road sector.') }}
                    </p>

                    <div class="flex items-center space-x-3 mb-6 mt-8">
                        <div class="w-8 h-8 rounded-lg bg-[#FFF200]/10 dark:bg-yellow-400/10 flex items-center justify-center">
                            <span class="text-lg font-bold text-[#FFF200] dark:text-yellow-400">2</span>
                        </div>
                        <h2 class="!mt-0 text-2xl font-bold text-[#231F20] dark:text-white">{{ __('Use License') }}</h2>
                    </div>
                    <p class="text-[#9B9EA4] dark:text-zinc-300 leading-relaxed">
                        {{ __('Permission is granted to temporarily access the materials (information or software) on KENHAVATE for personal, non-commercial transitory viewing only. This includes:') }}
                    </p>
                    <ul class="space-y-2 text-[#9B9EA4] dark:text-zinc-300">
                        <li class="flex items-start space-x-2">
                            <svg class="w-5 h-5 text-[#FFF200] dark:text-yellow-400 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <span>Submitting innovative ideas for road infrastructure improvements</span>
                        </li>
                        <li class="flex items-start space-x-2">
                            <svg class="w-5 h-5 text-[#FFF200] dark:text-yellow-400 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <span>Collaborating with other innovators</span>
                        </li>
                        <li class="flex items-start space-x-2">
                            <svg class="w-5 h-5 text-[#FFF200] dark:text-yellow-400 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <span>Accessing educational resources and documentation</span>
                        </li>
                    </ul>

                    <div class="flex items-center space-x-3 mb-6 mt-8">
                        <div class="w-8 h-8 rounded-lg bg-[#FFF200]/10 dark:bg-yellow-400/10 flex items-center justify-center">
                            <span class="text-lg font-bold text-[#FFF200] dark:text-yellow-400">3</span>
                        </div>
                        <h2 class="!mt-0 text-2xl font-bold text-[#231F20] dark:text-white">{{ __('Disclaimer') }}</h2>
                    </div>
                    <div class="bg-[#F8EBD5] dark:bg-zinc-900/50 rounded-xl p-6 border-l-4 border-[#FFF200] dark:border-yellow-400">
                        <p class="text-[#231F20] dark:text-zinc-300 leading-relaxed !mb-0">
                            {{ __('The materials on KENHAVATE are provided on an \'as is\' basis. KENHAVATE makes no warranties, expressed or implied, and hereby disclaims and negates all other warranties including without limitation, implied warranties or conditions of merchantability, fitness for a particular purpose, or non-infringement of intellectual property or other violation of rights.') }}
                        </p>
                    </div>

                    <div class="flex items-center space-x-3 mb-6 mt-8">
                        <div class="w-8 h-8 rounded-lg bg-[#FFF200]/10 dark:bg-yellow-400/10 flex items-center justify-center">
                            <span class="text-lg font-bold text-[#FFF200] dark:text-yellow-400">4</span>
                        </div>
                        <h2 class="!mt-0 text-2xl font-bold text-[#231F20] dark:text-white">{{ __('Limitations') }}</h2>
                    </div>
                    <p class="text-[#9B9EA4] dark:text-zinc-300 leading-relaxed">
                        {{ __('In no event shall KENHAVATE or its suppliers be liable for any damages (including, without limitation, damages for loss of data or profit, or due to business interruption) arising out of the use or inability to use the materials on KENHAVATE, even if KENHAVATE or a KENHAVATE authorized representative has been notified orally or in writing of the possibility of such damage.') }}
                    </p>

                    <div class="flex items-center space-x-3 mb-6 mt-8">
                        <div class="w-8 h-8 rounded-lg bg-[#FFF200]/10 dark:bg-yellow-400/10 flex items-center justify-center">
                            <span class="text-lg font-bold text-[#FFF200] dark:text-yellow-400">5</span>
                        </div>
                        <h2 class="!mt-0 text-2xl font-bold text-[#231F20] dark:text-white">{{ __('Intellectual Property') }}</h2>
                    </div>
                    <p class="text-[#9B9EA4] dark:text-zinc-300 leading-relaxed">
                        {{ __('Ideas submitted through KENHAVATE remain the intellectual property of the submitter. However, by submitting, you grant KeNHA a non-exclusive license to review, evaluate, and potentially implement your ideas with appropriate recognition and compensation as outlined in our Innovation Policy.') }}
                    </p>

                    <div class="flex items-center space-x-3 mb-6 mt-8">
                        <div class="w-8 h-8 rounded-lg bg-[#FFF200]/10 dark:bg-yellow-400/10 flex items-center justify-center">
                            <span class="text-lg font-bold text-[#FFF200] dark:text-yellow-400">6</span>
                        </div>
                        <h2 class="!mt-0 text-2xl font-bold text-[#231F20] dark:text-white">{{ __('Privacy Policy') }}</h2>
                    </div>
                    <p class="text-[#9B9EA4] dark:text-zinc-300 leading-relaxed">
                        {{ __('Your privacy is important to us. We collect and process personal data in accordance with Kenya\'s Data Protection Act, 2019. Please review our Privacy Policy, which also governs your use of KENHAVATE, to understand our practices regarding data collection, usage, and protection.') }}
                    </p>

                    <div class="flex items-center space-x-3 mb-6 mt-8">
                        <div class="w-8 h-8 rounded-lg bg-[#FFF200]/10 dark:bg-yellow-400/10 flex items-center justify-center">
                            <span class="text-lg font-bold text-[#FFF200] dark:text-yellow-400">7</span>
                        </div>
                        <h2 class="!mt-0 text-2xl font-bold text-[#231F20] dark:text-white">{{ __('Governing Law') }}</h2>
                    </div>
                    <p class="text-[#9B9EA4] dark:text-zinc-300 leading-relaxed">
                        {{ __('Any claim relating to KENHAVATE shall be governed by the laws of the Republic of Kenya without regard to its conflict of law provisions. Disputes shall be resolved through the Kenyan judicial system.') }}
                    </p>
                </div>
            </div>

            <!-- Gradient Divider -->
            <div class="h-px bg-gradient-to-r from-transparent via-[#9B9EA4] dark:via-zinc-600 to-transparent"></div>

            <!-- Acceptance Form -->
            <div class="p-8 bg-gradient-to-br from-[#F8EBD5]/30 via-white to-white dark:from-zinc-800/50 dark:via-zinc-800 dark:to-zinc-800">
                <form wire:submit="acceptTerms" class="space-y-6">
                    
                    <!-- Enhanced Checkbox with Animation -->
                    <div class="bg-white dark:bg-zinc-900/50 rounded-xl p-6 border-2 border-dashed border-[#9B9EA4]/30 dark:border-zinc-600/30 transition-all duration-300 hover:border-[#FFF200] dark:hover:border-yellow-400 hover:shadow-lg">
                        <flux:checkbox
                            wire:model.live="accepted"
                            :label="__('I have read and agree to the Terms and Conditions')"
                            required
                            class="text-lg"
                        />
                        <p class="mt-2 text-xs text-[#9B9EA4] dark:text-zinc-400 ml-6">
                            {{ __('Version 1.0 • Last updated: ') . date('F Y') }}
                        </p>
                    </div>

                    <!-- Action Buttons with Modern Styling -->
                    <div class="flex flex-col sm:flex-row gap-4">
                        <flux:button
                            icon="check-badge"
                            variant="primary"
                            type="submit"
                            class="flex-1 justify-center rounded-xl bg-[#FFF200] dark:bg-yellow-400 px-6 py-4 text-base font-semibold text-[#231F20] dark:text-zinc-900 shadow-lg hover:bg-[#FFF200]/90 dark:hover:bg-yellow-300 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#FFF200] dark:focus-visible:outline-yellow-400 transition-all duration-200 hover:shadow-xl hover:scale-[1.02] disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:scale-100"
                            :disabled="!$accepted"
                        >
                            {{ __('Accept & Continue') }}
                        </flux:button>

                        <flux:button
                            icon="x-mark"
                            variant="danger"
                            type="button"
                            wire:click="declineTerms"
                            class="flex-1 justify-center transition-all duration-200 hover:scale-[1.02]"
                        >
                            {{ __('Decline & Logout') }}
                        </flux:button>
                    </div>

                    <!-- Helper Text -->
                    <div class="text-center">
                        <p class="text-sm text-[#9B9EA4] dark:text-zinc-400 flex items-center justify-center space-x-2">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                            </svg>
                            <span>{{ __('By accepting, you agree to our data processing practices outlined in our Privacy Policy.') }}</span>
                        </p>
                    </div>
                </form>
            </div>
        </div>

        <!-- Trust Indicators -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-8">
            <div class="bg-white dark:bg-zinc-800 rounded-xl p-4 border border-[#9B9EA4]/20 dark:border-zinc-700 flex items-center space-x-3 hover:shadow-lg transition-all duration-300">
                <div class="flex-shrink-0 w-10 h-10 rounded-lg bg-[#FFF200]/10 dark:bg-yellow-400/10 flex items-center justify-center">
                    <svg class="w-5 h-5 text-[#FFF200] dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-sm font-semibold text-[#231F20] dark:text-white">Secure Platform</p>
                    <p class="text-xs text-[#9B9EA4] dark:text-zinc-400">Your data is protected</p>
                </div>
            </div>

            <div class="bg-white dark:bg-zinc-800 rounded-xl p-4 border border-[#9B9EA4]/20 dark:border-zinc-700 flex items-center space-x-3 hover:shadow-lg transition-all duration-300">
                <div class="flex-shrink-0 w-10 h-10 rounded-lg bg-[#FFF200]/10 dark:bg-yellow-400/10 flex items-center justify-center">
                    <svg class="w-5 h-5 text-[#FFF200] dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-sm font-semibold text-[#231F20] dark:text-white">GDPR Compliant</p>
                    <p class="text-xs text-[#9B9EA4] dark:text-zinc-400">Privacy first approach</p>
                </div>
            </div>

            <div class="bg-white dark:bg-zinc-800 rounded-xl p-4 border border-[#9B9EA4]/20 dark:border-zinc-700 flex items-center space-x-3 hover:shadow-lg transition-all duration-300">
                <div class="flex-shrink-0 w-10 h-10 rounded-lg bg-[#FFF200]/10 dark:bg-yellow-400/10 flex items-center justify-center">
                    <svg class="w-5 h-5 text-[#FFF200] dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-sm font-semibold text-[#231F20] dark:text-white">24/7 Support</p>
                    <p class="text-xs text-[#9B9EA4] dark:text-zinc-400">We're here to help</p>
                </div>
            </div>
        </div>
    </div>

    <style>
        /* Custom Scrollbar Styling */
        .custom-scrollbar::-webkit-scrollbar {
            width: 8px;
        }
        
        .custom-scrollbar::-webkit-scrollbar-track {
            background: rgba(155, 158, 164, 0.1);
            border-radius: 10px;
        }
        
        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: linear-gradient(to bottom, #FFF200, #f5dc00);
            border-radius: 10px;
        }
        
        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(to bottom, #f5dc00, #e6ce00);
        }
        
        .dark .custom-scrollbar::-webkit-scrollbar-thumb {
            background: linear-gradient(to bottom, #facc15, #eab308);
        }
    </style>
</div>