<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SettingsController;

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/general');

    Route::livewire('settings/profile', 'pages::settings.profile')->name('profile.edit');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::livewire('settings/appearance', 'pages::settings.appearance')->name('appearance.edit');

    Route::livewire('account/security', 'pages::settings.security')
        ->middleware([
            'password.confirm',
        ])
        ->name('security.edit');

    foreach (config('settings_sections') as $section) {
        Route::get('settings/' . $section['path'], [SettingsController::class, 'show'])
            ->name('settings.' . $section['route'])
            ->defaults('path', $section['path']);

        Route::post('settings/' . $section['path'], [SettingsController::class, 'update'])
            ->name('settings.' . $section['route'] . '.update')
            ->defaults('path', $section['path']);

        Route::post('settings/' . $section['path'] . '/reset', [SettingsController::class, 'reset'])
            ->name('settings.' . $section['route'] . '.reset')
            ->defaults('path', $section['path']);
    }
});

Route::get('.well-known/passkey-endpoints', function () {
    return response()->json([
        'enroll' => route('security.edit'),
        'manage' => route('security.edit'),
    ]);
})->name('well-known.passkeys');
