<?php

use Livewire\Volt\Component;

new #[Layout('components.layouts.app')] class extends Component {
    /**
     * Request a review of the account ban.
     */
    public function requestReview(): void
    {
        // TODO: Implement account review request logic
        // This could send an email to administrators or create a support ticket

        session()->flash('message', 'Account review request submitted. You will be notified of the decision.');
    }
}; ?>

<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg">
            <div class="p-6 lg:p-8 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
                <div class="flex items-center">
                    <flux:icon name="exclamation-triangle" class="w-8 h-8 text-red-500" />
                    <h1 class="ml-2 text-2xl font-medium text-gray-900 dark:text-white">
                        {{ __('Account Suspended') }}
                    </h1>
                </div>

                <div class="mt-6 text-gray-500 dark:text-gray-400 leading-relaxed">
                    <p class="mb-4">
                        {{ __('Your account has been suspended due to a violation of our terms of service.') }}
                    </p>

                    <p class="mb-4">
                        {{ __('If you believe this suspension was made in error, you can request a review of your account status.') }}
                    </p>

                    @if (session('message'))
                        <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded">
                            {{ session('message') }}
                        </div>
                    @endif

                    <div class="mt-6">
                        <flux:button variant="primary" wire:click="requestReview">
                            {{ __('Request Account Review') }}
                        </flux:button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>