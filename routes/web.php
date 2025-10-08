<?php

use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;
use Livewire\Volt\Volt;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::middleware(['auth', 'verified', 'check.account.status', 'check.profile.completion', 'check.terms.accepted'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');

    // Ideas
    Volt::route('ideas/submit', 'ideas.submit')->name('ideas.submit');
    Volt::route('ideas/edit_draft/{draft}', 'ideas.submit')->name('ideas.edit_draft.draft')->where('draft', '[a-zA-Z0-9-]+');
    Volt::route('ideas/my-ideas', 'ideas.table')->name('ideas.table');
    Volt::route('ideas/{idea}/comments', 'ideas.comments')->name('ideas.comments')->where('idea', '[0-9]+');
});

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

    // Profile setup (for new users) - redirect to settings profile
    Route::get('profile/setup', function () {
        return redirect()->route('profile.edit');
    })->name('profile.setup');
    Route::post('profile/setup', function () {
        return redirect()->route('terms.show');
    })->name('profile.complete');

    // Terms and conditions
    Volt::route('terms-conditions', 'terms.show')
        ->middleware('check.profile.completion')
        ->name('terms.show');

    // Notifications
    Volt::route('notifications', 'notifications.list')->name('notifications.index');
});

require __DIR__.'/auth.php';
