<?php

use Livewire\Volt\Component;

new #[Layout('components.layouts.app')] class extends Component {
    /**
     * Contact support for account reactivation.
     */
    public function contactSupport(): void
    {
        // TODO: Implement support contact logic
        // This could redirect to a support form or send an email

        session()->flash('message', 'Support request submitted. Our team will contact you soon.');
    }
}; ?>

<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg">
            <div class="p-6 lg:p-8 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
                <div class="flex items-center">
                    <flux:icon name="exclamation-circle" class="w-8 h-8 text-yellow-500" />
                    <h1 class="ml-2 text-2xl font-medium text-gray-900 dark:text-white">
                        {{ __('Account Disabled') }}
                    </h1>
                </div>

                <div class="mt-6 text-gray-500 dark:text-gray-400 leading-relaxed">
                    <p class="mb-4">
                        {{ __('Your account has been temporarily disabled. This may be due to security concerns or administrative action.') }}
                    </p>

                    <p class="mb-4">
                        {{ __('Please contact your system administrator or HR department for assistance in reactivating your account.') }}
                    </p>

                    @if (session('message'))
                        <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded">
                            {{ session('message') }}
                        </div>
                    @endif

                    <div class="mt-6">
                        <flux:button variant="primary" wire:click="contactSupport">
                            {{ __('Contact Support') }}
                        </flux:button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>