<?php

use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.auth')] class extends Component {
    // Component logic here
}; ?>

<div
    class="relative w-full h-auto flex flex-col gap-8"
    x-cloak
    x-data="{
        showRecoveryInput: @js($errors->has('recovery_code')),
        code: '',
        recovery_code: '',
        toggleInput() {
            this.showRecoveryInput = !this.showRecoveryInput;

            this.code = '';
            this.recovery_code = '';

            $dispatch('clear-2fa-auth-code');
    
            $nextTick(() => {
                this.showRecoveryInput
                    ? this.$refs.recovery_code?.focus()
                    : $dispatch('focus-2fa-auth-code');
            });
        },
    }"
>
        <div x-show="!showRecoveryInput">
            <x-auth-header
                :title="__('Authentication Code')"
                :description="__('Enter the authentication code provided by your authenticator application.')"
            />
        </div>

        <div x-show="showRecoveryInput">
            <x-auth-header
                :title="__('Recovery Code')"
                :description="__('Please confirm access to your account by entering one of your emergency recovery codes.')"
            />
        </div>

        <!-- Session Status -->
        <x-auth-session-status class="text-center" :status="session('status')" />

        <!-- 2FA Info Box -->
        <div class="rounded-lg bg-[#F8EBD5] dark:bg-zinc-800/50 p-4 border border-[#FFF200] dark:border-yellow-400">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-[#FFF200] dark:text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-[#231F20] dark:text-white">{{ __('Secure Access Required') }}</p>
                    <p class="text-sm text-[#9B9EA4] dark:text-zinc-400">{{ __('Enter your 6-digit code from your authenticator app to continue.') }}</p>
                </div>
            </div>
        </div>

        <form method="POST" action="{{ route('two-factor.login.store') }}" class="flex flex-col gap-8">
            @csrf

            <div class="space-y-5 text-center">
                <div x-show="!showRecoveryInput">
                    <div class="flex items-center justify-center my-5">
                        <x-input-otp
                            name="code"
                            digits="6"
                            autocomplete="one-time-code"
                            x-model="code"
                        />
                    </div>

                    @error('code')
                        <p class="text-sm text-red-600 dark:text-red-400 text-center font-medium">
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <div x-show="showRecoveryInput">
                    <div class="my-5">
                        <flux:input
                            type="text"
                            name="recovery_code"
                            x-ref="recovery_code"
                            x-bind:required="showRecoveryInput"
                            autocomplete="one-time-code"
                            x-model="recovery_code"
                            placeholder="Enter recovery code"
                        />
                    </div>

                    @error('recovery_code')
                        <p class="text-sm text-red-600 dark:text-red-400 text-center font-medium">
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <div class="space-y-4">
                    <flux:button variant="primary" type="submit" class="w-full justify-center rounded-lg bg-[#FFF200] dark:bg-yellow-400 px-4 py-3 text-sm font-semibold text-[#231F20] dark:text-zinc-900 shadow-lg hover:bg-[#FFF200]/90 dark:hover:bg-yellow-300 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#FFF200] dark:focus-visible:outline-yellow-400 transition-all duration-200 hover:shadow-xl">
                        {{ __('Continue') }}
                    </flux:button>
                </div>

            <div class="mt-5 space-x-0.5 text-sm leading-5 text-center">
                <span class="opacity-50">{{ __('or you can') }}</span>
                <div class="inline font-medium underline cursor-pointer opacity-80">
                    <span x-show="!showRecoveryInput" @click="toggleInput()">{{ __('login using a recovery code') }}</span>
                    <span x-show="showRecoveryInput" @click="toggleInput()">{{ __('login using an authentication code') }}</span>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex flex-col-reverse sm:flex-row items-center gap-3">
                <flux:button
                    variant="ghost"
                    type="button"
                    onclick="window.history.back()"
                    class="w-full sm:w-auto border-2 border-[#9B9EA4] dark:border-zinc-600 text-sm text-[#231F20] dark:text-white hover:text-[#231F20] dark:hover:text-white/80 transition-colors duration-200"
                >
                    {{ __('← Back') }}
                </flux:button>
            </div>
        </form>
    </div>
</div>
