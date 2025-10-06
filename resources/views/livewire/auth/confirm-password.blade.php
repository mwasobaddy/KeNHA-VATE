<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.auth')] class extends Component {
    public string $password = '';

    /**
     * Confirm the user's password.
     */
    public function confirmPassword(): void
    {
        $this->validate([
            'password' => 'required|string',
        ]);

        if (! Hash::check($this->password, Auth::user()->password)) {
            throw ValidationException::withMessages([
                'password' => 'The provided password does not match your current password.',
            ]);
        }

        Session::put('auth.password_confirmed_at', time());

        $this->redirect(
            Session::pull('url.intended', route('dashboard')),
            navigate: true
        );
    }
}; ?>

<div class="flex flex-col gap-6">
    <x-auth-header
        :title="__('Confirm password')"
        :description="__('This is a secure area of the application. Please confirm your password before continuing.')"
    />

    <x-auth-session-status class="text-center" :status="session('status')" />

    <form wire:submit="confirmPassword" class="flex flex-col gap-6">
        <flux:input
            wire:model="password"
            name="password"
            :label="__('Password')"
            type="password"
            required
            autocomplete="current-password"
            :placeholder="__('Password')"
            viewable
        />

        <flux:button variant="primary" type="submit" class="w-full" data-test="confirm-password-button">
            Confirm
        </flux:button>
    </form>
</div>
