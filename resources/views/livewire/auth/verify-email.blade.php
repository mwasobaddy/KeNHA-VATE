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

        Session::put('immediate_popup_notification', [
            'type' => 'success',
            'title' => 'Verification Email Sent',
            'message' => 'A new verification link has been sent to the email address you provided during registration.',
            'duration' => 5000,
        ]);

        $this->redirect(route('verification.notice'), navigate: true);
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
    <!-- Email Verification Instructions -->
    <div class="w-full text-center">
        <div class="rounded-lg bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 p-6">
            <div class="flex flex-col items-center gap-4">
                <div class="flex-shrink-0">
                    <flux:icon name="envelope" class="h-12 w-12 text-blue-600 dark:text-blue-400" />
                </div>
                <div class="text-center">
                    <h3 class="text-lg font-semibold text-blue-900 dark:text-blue-100 mb-2">
                        {{ __('Check Your Email') }}
                    </h3>
                    <p class="text-sm text-blue-700 dark:text-blue-300 mb-4">
                        {{ __('We\'ve sent a verification link to your email address. Click the link in the email to verify your account and continue.') }}
                    </p>
                    <p class="text-xs text-blue-600 dark:text-blue-400">
                        {{ __('Didn\'t receive the email? Check your spam folder or click the button below to resend.') }}
                    </p>
                </div>
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
