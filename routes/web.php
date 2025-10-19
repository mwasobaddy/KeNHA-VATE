<?php

use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;
use Livewire\Volt\Volt;
use App\Http\Controllers\PdfController;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::middleware(['auth', 'check.session.validity', 'check.account.status', 'check.profile.completion', 'check.terms.accepted'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');

    // Ideas
    Volt::route('ideas/submit', 'ideas.submit')->name('ideas.submit');
    Volt::route('ideas/edit_draft/{draft}', 'ideas.submit')->name('ideas.edit_draft.draft')->where('draft', '[a-zA-Z0-9-]+');
    Volt::route('ideas/my-ideas', 'ideas.table')->name('ideas.table');
    Volt::route('ideas/show/{idea}', 'ideas.show')->name('ideas.show')->where('idea', '[a-zA-Z0-9-]+');
    Volt::route('ideas/comments/{idea}/{comment?}', 'ideas.comments')->name('ideas.comments')->where(['idea' => '[a-zA-Z0-9-]+', 'comment' => '[0-9]+']);
    Volt::route('ideas/pdf-viewer/{idea}', 'ideas.pdf-viewer')->name('ideas.pdf-viewer')->where('idea', '[a-zA-Z0-9-]+');
});

// Public Ideas Routes (no authentication required)
Route::middleware(['check.session.validity'])->group(function () {
    Volt::route('ideas/public', 'ideas.public')->name('ideas.public');
    Volt::route('ideas/public/{idea}', 'ideas.show')->name('ideas.public.show')->where('idea', '[a-zA-Z0-9-]+');
    Route::get('ideas/pdf/{idea}', [PdfController::class, 'download'])->name('ideas.pdf')->where('idea', '[a-zA-Z0-9-]+');
});

Route::middleware(['auth', 'check.session.validity'])->group(function () {
    // Account status routes
    Volt::route('account/banned', 'account.banned')->name('account.banned');
    Volt::route('account/disabled', 'account.disabled')->name('account.disabled');

    // User settings
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
