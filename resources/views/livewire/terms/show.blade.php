<?php

use App\Services\UserService;
use Illuminate\Support\Facades\Auth;
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
            $this->addError('accepted', 'You must accept the terms and conditions to continue.');
            return;
        }

        $user = Auth::user();
        $userService = app(UserService::class);

        // Accept terms with current version
        $userService->acceptTerms($user, '1.0');

        // Redirect to dashboard
        $this->redirect(route('dashboard'), navigate: true);
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

<div class="max-w-4xl mx-auto space-y-6">
    <div class="text-center">
        <h1 class="text-2xl font-bold text-zinc-900 dark:text-zinc-100">
            {{ __('Terms and Conditions') }}
        </h1>
        <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">
            {{ __('Please read and accept our terms and conditions to continue using KENHAVATE.') }}
        </p>
    </div>

    <!-- Terms Content -->
    <div class="bg-white dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700 p-6">
        <div class="prose dark:prose-invert max-w-none">
            <h2>{{ __('1. Acceptance of Terms') }}</h2>
            <p>{{ __('By accessing and using KENHAVATE, you accept and agree to be bound by the terms and provision of this agreement.') }}</p>

            <h2>{{ __('2. Use License') }}</h2>
            <p>{{ __('Permission is granted to temporarily access the materials (information or software) on KENHAVATE for personal, non-commercial transitory viewing only.') }}</p>

            <h2>{{ __('3. Disclaimer') }}</h2>
            <p>{{ __('The materials on KENHAVATE are provided on an \'as is\' basis. KENHAVATE makes no warranties, expressed or implied, and hereby disclaims and negates all other warranties including without limitation, implied warranties or conditions of merchantability, fitness for a particular purpose, or non-infringement of intellectual property or other violation of rights.') }}</p>

            <h2>{{ __('4. Limitations') }}</h2>
            <p>{{ __('In no event shall KENHAVATE or its suppliers be liable for any damages (including, without limitation, damages for loss of data or profit, or due to business interruption) arising out of the use or inability to use the materials on KENHAVATE, even if KENHAVATE or a KENHAVATE authorized representative has been notified orally or in writing of the possibility of such damage.') }}</p>

            <h2>{{ __('5. Accuracy of Materials') }}</h2>
            <p>{{ __('The materials appearing on KENHAVATE could include technical, typographical, or photographic errors. KENHAVATE does not warrant that any of the materials on its website are accurate, complete, or current.') }}</p>

            <h2>{{ __('6. Privacy Policy') }}</h2>
            <p>{{ __('Your privacy is important to us. Please review our Privacy Policy, which also governs your use of KENHAVATE, to understand our practices.') }}</p>

            <h2>{{ __('7. Governing Law') }}</h2>
            <p>{{ __('Any claim relating to KENHAVATE shall be governed by the laws of Kenya without regard to its conflict of law provisions.') }}</p>
        </div>
    </div>

    <!-- Acceptance Form -->
    <div class="bg-white dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700 p-6">
        <form wire:submit="acceptTerms" class="space-y-4">
            <flux:checkbox
                wire:model.live="accepted"
                :label="__('I have read and agree to the Terms and Conditions')"
                required
            />

            <div class="flex flex-col sm:flex-row gap-3">
                <flux:button
                    variant="primary"
                    type="submit"
                    class="flex-1"
                    :disabled="!$accepted"
                >
                    {{ __('Accept & Continue') }}
                </flux:button>

                <flux:button
                    variant="danger"
                    type="button"
                    wire:click="declineTerms"
                    class="flex-1"
                >
                    {{ __('Decline') }}
                </flux:button>
            </div>
        </form>
    </div>

    <div class="text-center text-sm text-zinc-600 dark:text-zinc-400">
        <p>{{ __('By accepting these terms, you agree to our data processing practices outlined in our Privacy Policy.') }}</p>
    </div>
</div>