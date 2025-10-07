{{-- 
    Modernized Settings Layout
    - Enhanced navigation with icons and active states
    - Responsive mobile-first design with sticky navigation
    - Smooth transitions and hover effects
    - Brand-consistent color scheme
--}}

<div class="flex items-start max-md:flex-col gap-8 lg:gap-12">
    {{-- Side Navigation - Enhanced with icons and modern styling --}}
    <div class="w-full md:w-[260px] md:sticky md:top-24">
        {{-- Mobile: Horizontal scroll navigation --}}
        <div class="md:hidden overflow-x-auto pb-4 -mx-4 px-4">
            <div class="flex gap-2 min-w-max">
                <a href="{{ route('profile.edit') }}" 
                   wire:navigate
                   class="flex items-center gap-2 px-4 py-2.5 rounded-lg text-sm font-medium transition-all duration-200
                          {{ request()->routeIs('profile.edit') 
                             ? 'bg-[#FFF200] dark:bg-yellow-400 text-[#231F20] dark:text-zinc-900 shadow-md' 
                             : 'bg-white dark:bg-zinc-800 text-zinc-600 dark:text-zinc-400 hover:bg-zinc-50 dark:hover:bg-zinc-700/50 border border-zinc-200 dark:border-zinc-700' }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                    <span>{{ __('Profile') }}</span>
                </a>

                <a href="{{ route('password.edit') }}" 
                   wire:navigate
                   class="flex items-center gap-2 px-4 py-2.5 rounded-lg text-sm font-medium transition-all duration-200
                          {{ request()->routeIs('password.edit') 
                             ? 'bg-[#FFF200] dark:bg-yellow-400 text-[#231F20] dark:text-zinc-900 shadow-md' 
                             : 'bg-white dark:bg-zinc-800 text-zinc-600 dark:text-zinc-400 hover:bg-zinc-50 dark:hover:bg-zinc-700/50 border border-zinc-200 dark:border-zinc-700' }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                    </svg>
                    <span>{{ __('Password') }}</span>
                </a>

                @if (Laravel\Fortify\Features::canManageTwoFactorAuthentication())
                    <a href="{{ route('two-factor.show') }}" 
                       wire:navigate
                       class="flex items-center gap-2 px-4 py-2.5 rounded-lg text-sm font-medium transition-all duration-200
                              {{ request()->routeIs('two-factor.show') 
                                 ? 'bg-[#FFF200] dark:bg-yellow-400 text-[#231F20] dark:text-zinc-900 shadow-md' 
                                 : 'bg-white dark:bg-zinc-800 text-zinc-600 dark:text-zinc-400 hover:bg-zinc-50 dark:hover:bg-zinc-700/50 border border-zinc-200 dark:border-zinc-700' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                        </svg>
                        <span>{{ __('2FA') }}</span>
                    </a>
                @endif

                <a href="{{ route('appearance.edit') }}" 
                   wire:navigate
                   class="flex items-center gap-2 px-4 py-2.5 rounded-lg text-sm font-medium transition-all duration-200
                          {{ request()->routeIs('appearance.edit') 
                             ? 'bg-[#FFF200] dark:bg-yellow-400 text-[#231F20] dark:text-zinc-900 shadow-md' 
                             : 'bg-white dark:bg-zinc-800 text-zinc-600 dark:text-zinc-400 hover:bg-zinc-50 dark:hover:bg-zinc-700/50 border border-zinc-200 dark:border-zinc-700' }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01" />
                    </svg>
                    <span>{{ __('Appearance') }}</span>
                </a>
            </div>
        </div>

        {{-- Desktop: Vertical navigation --}}
        <nav class="hidden md:block bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 p-2 shadow-sm">
            <div class="space-y-1">
                <a href="{{ route('profile.edit') }}" 
                   wire:navigate
                   class="flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-medium transition-all duration-200 group
                          {{ request()->routeIs('profile.edit') 
                             ? 'bg-[#FFF200] dark:bg-yellow-400 text-[#231F20] dark:text-zinc-900 shadow-md' 
                             : 'text-zinc-700 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-700/50' }}">
                    <svg class="w-5 h-5 transition-transform duration-200 {{ request()->routeIs('profile.edit') ? '' : 'group-hover:scale-110' }}" 
                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                    <span>{{ __('Profile') }}</span>
                    @if(request()->routeIs('profile.edit'))
                        <svg class="w-4 h-4 ml-auto" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                        </svg>
                    @endif
                </a>

                <a href="{{ route('password.edit') }}" 
                   wire:navigate
                   class="flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-medium transition-all duration-200 group
                          {{ request()->routeIs('password.edit') 
                             ? 'bg-[#FFF200] dark:bg-yellow-400 text-[#231F20] dark:text-zinc-900 shadow-md' 
                             : 'text-zinc-700 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-700/50' }}">
                    <svg class="w-5 h-5 transition-transform duration-200 {{ request()->routeIs('password.edit') ? '' : 'group-hover:scale-110' }}" 
                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                    </svg>
                    <span>{{ __('Password') }}</span>
                    @if(request()->routeIs('password.edit'))
                        <svg class="w-4 h-4 ml-auto" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                        </svg>
                    @endif
                </a>

                @if (Laravel\Fortify\Features::canManageTwoFactorAuthentication())
                    <a href="{{ route('two-factor.show') }}" 
                       wire:navigate
                       class="flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-medium transition-all duration-200 group
                              {{ request()->routeIs('two-factor.show') 
                                 ? 'bg-[#FFF200] dark:bg-yellow-400 text-[#231F20] dark:text-zinc-900 shadow-md' 
                                 : 'text-zinc-700 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-700/50' }}">
                        <svg class="w-5 h-5 transition-transform duration-200 {{ request()->routeIs('two-factor.show') ? '' : 'group-hover:scale-110' }}" 
                             fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                        </svg>
                        <span>{{ __('Two-Factor Auth') }}</span>
                        @if(request()->routeIs('two-factor.show'))
                            <svg class="w-4 h-4 ml-auto" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                        @endif
                    </a>
                @endif

                <a href="{{ route('appearance.edit') }}" 
                   wire:navigate
                   class="flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-medium transition-all duration-200 group
                          {{ request()->routeIs('appearance.edit') 
                             ? 'bg-[#FFF200] dark:bg-yellow-400 text-[#231F20] dark:text-zinc-900 shadow-md' 
                             : 'text-zinc-700 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-700/50' }}">
                    <svg class="w-5 h-5 transition-transform duration-200 {{ request()->routeIs('appearance.edit') ? '' : 'group-hover:scale-110' }}" 
                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01" />
                    </svg>
                    <span>{{ __('Appearance') }}</span>
                    @if(request()->routeIs('appearance.edit'))
                        <svg class="w-4 h-4 ml-auto" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                        </svg>
                    @endif
                </a>
            </div>
        </nav>

        {{-- Help Card - Desktop only --}}
        <div class="hidden md:block mt-6 bg-gradient-to-br from-[#F8EBD5] to-white dark:from-zinc-800 dark:to-zinc-800/50 rounded-xl border border-zinc-200 dark:border-zinc-700 p-5 shadow-sm">
            <div class="flex items-start gap-3">
                <div class="flex-shrink-0 w-10 h-10 bg-[#FFF200] dark:bg-yellow-400 rounded-full flex items-center justify-center">
                    <svg class="w-5 h-5 text-[#231F20] dark:text-zinc-900" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div class="flex-1">
                    <h4 class="text-sm font-semibold text-[#231F20] dark:text-white mb-1">
                        {{ __('Need Help?') }}
                    </h4>
                    <p class="text-xs text-zinc-600 dark:text-zinc-400 leading-relaxed">
                        {{ __('Check our documentation or contact support for assistance.') }}
                    </p>
                    <a href="#" class="inline-flex items-center gap-1 mt-3 text-xs font-medium text-[#231F20] dark:text-[#FFF200] hover:underline">
                        {{ __('Get Help') }}
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- Main Content Area --}}
    <div class="flex-1 min-w-0">
        {{-- Header Section --}}
        <div class="mb-8">
            <flux:heading class="text-2xl lg:text-3xl font-bold text-[#231F20] dark:text-white">
                {{ $heading ?? '' }}
            </flux:heading>
            <flux:subheading class="mt-2 text-zinc-600 dark:text-zinc-400">
                {{ $subheading ?? '' }}
            </flux:subheading>
        </div>

        {{-- Content Container --}}
        <div class="w-full max-w-4xl">
            {{ $slot }}
        </div>
    </div>
</div>

<style>
    /* Smooth scroll behavior */
    html {
        scroll-behavior: smooth;
    }

    /* Custom scrollbar for mobile navigation */
    @media (max-width: 768px) {
        .overflow-x-auto::-webkit-scrollbar {
            height: 4px;
        }
        .overflow-x-auto::-webkit-scrollbar-track {
            background: transparent;
        }
        .overflow-x-auto::-webkit-scrollbar-thumb {
            background: rgba(155, 158, 164, 0.3);
            border-radius: 4px;
        }
    }
</style>