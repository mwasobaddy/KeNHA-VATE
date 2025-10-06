<?php

use App\Livewire\Actions\Logout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.auth')] class extends Component {
    /**
     * Send an email verification notification to the user.
     */
    public function sendVerification(): void
    {
        Auth::user()->sendEmailVerificationNotification();

        Session::flash('status', 'verification-link-sent');
    }

    /**
     * Log the current user out of the application.
     */
    public function logout(Logout $logout): void
    {
        $logout();

        $this->redirect('/', navigate: true);
    }

    /**
     * Handle the component's rendering hook.
     */
    public function rendering(View $view): void
    {
        if (Auth::user()->hasVerifiedEmail()) {
            $this->redirectIntended(default: route('dashboard', absolute: false), navigate: true);

            return;
        }
    }
}; ?>

<div class="mt-4 flex flex-col gap-6">
    <!-- Notice box (yellow border) -->
    <div class="w-full">
        <div class="rounded-lg border border-yellow-400/60 bg-yellow-50/40 dark:bg-neutral-900/30 p-4 flex items-start gap-3">
            <div class="flex-shrink-0">
                <span class="inline-flex items-center justify-center h-9 w-9 rounded-full bg-[#FFF200] dark:bg-yellow-400 text-[#231F20] dark:text-zinc-900 shadow">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                </span>
            </div>

            <div class="flex-1">
                <p class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">{{ __('Please verify your email address by clicking on the link we just emailed to you.') }}</p>
                @if (session('status') == 'verification-link-sent')
                    <p class="mt-2 text-sm font-medium text-green-700 dark:text-green-300">
                        {{ __('A new verification link has been sent to the email address you provided during registration.') }}
                    </p>
                @endif
            </div>
        </div>
    </div>

    <!-- Actions -->
    <div class="flex flex-col items-center justify-between space-y-3">
        <div class="space-y-4 w-full">
            <flux:button wire:click="sendVerification" variant="primary" class="w-full justify-center rounded-lg bg-[#FFF200] dark:bg-yellow-400 px-4 py-3 text-sm font-semibold text-[#231F20] dark:text-zinc-900 shadow-lg hover:bg-[#FFF200]/90 dark:hover:bg-yellow-300 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#FFF200] dark:focus-visible:outline-yellow-400 transition-all duration-200 hover:shadow-xl">
                {{ __('Resend verification email') }}
            </flux:button>
        </div>

        <flux:link class="text-sm cursor-pointer" wire:click="logout" data-test="logout-button">
            {{ __('Log out') }}
        </flux:link>
    </div>
</div>
