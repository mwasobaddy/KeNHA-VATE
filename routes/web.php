<?php

use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;
use Livewire\Volt\Volt;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified', 'check.account.status', 'check.profile.completion', 'check.terms.accepted'])
    ->name('dashboard');

// Account status routes
Volt::route('account/banned', 'account.banned')->name('account.banned');
Volt::route('account/disabled', 'account.disabled')->name('account.disabled');

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Volt::route('settings/profile', 'settings.profile')->name('profile.edit');
    Volt::route('settings/password', 'settings.password')->name('password.edit');
    Volt::route('settings/appearance', 'settings.appearance')->name('appearance.edit');

    Volt::route('settings/two-factor', 'settings.two-factor')
        ->middleware(
            when(
                Features::canManageTwoFactorAuthentication()
                    && Features::optionEnabled(Features::twoFactorAuthentication(), 'confirmPassword'),
                ['password.confirm'],
                [],
            ),
        )
        ->name('two-factor.show');

    // Profile setup (for new users)
    Volt::route('profile/setup', 'profile.setup')
        ->name('profile.setup');

    // Terms and conditions
    Volt::route('terms-conditions', 'terms.show')
        ->middleware('check.profile.completion')
        ->name('terms.show');

    // Notifications
    Volt::route('notifications', 'notifications.list')->name('notifications.index');
});

require __DIR__.'/auth.php';
