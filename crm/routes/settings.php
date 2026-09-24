<?php

use App\Http\Controllers\Settings\DestroyProfileController;
use App\Http\Controllers\Settings\DestroySessionController;
use App\Http\Controllers\Settings\EditNotificationChannelsController;
use App\Http\Controllers\Settings\EditProfileController;
use App\Http\Controllers\Settings\EditSecurityController;
use App\Http\Controllers\Settings\IndexSessionController;
use App\Http\Controllers\Settings\LogoutOtherSessionsController;
use App\Http\Controllers\Settings\SendTestNotificationController;
use App\Http\Controllers\Settings\UpdateNotificationChannelsController;
use App\Http\Controllers\Settings\UpdatePasswordController;
use App\Http\Controllers\Settings\UpdateProfileController;
use Illuminate\Auth\Middleware\RequirePassword;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', '/settings/profile');

    Route::get('settings/profile', EditProfileController::class)->name('profile.edit');
    Route::patch('settings/profile', UpdateProfileController::class)->name('profile.update');

    Route::get('settings/notifications', EditNotificationChannelsController::class)->name('notifications.edit');
    Route::put('settings/notifications', UpdateNotificationChannelsController::class)->name('notifications.update');
    Route::post('settings/notifications/test', SendTestNotificationController::class)->name('notifications.test');

    Route::get('settings/sessions', IndexSessionController::class)->name('sessions.index');
    Route::delete('settings/sessions/{id}', DestroySessionController::class)->name('sessions.destroy');
    Route::post('settings/sessions/logout-other', LogoutOtherSessionsController::class)->name('sessions.logout-other');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::delete('settings/profile', DestroyProfileController::class)->name('profile.destroy');

    Route::get('settings/security', EditSecurityController::class)
        ->middleware(RequirePassword::class)
        ->name('security.edit');

    Route::put('settings/password', UpdatePasswordController::class)
        ->middleware('throttle:6,1')
        ->name('user-password.update');

    Route::redirect('settings/appearance', '/settings/profile')->name('appearance.edit');
});

Route::get('.well-known/passkey-endpoints', function () {
    return response()->json([
        'enroll' => route('security.edit'),
        'manage' => route('security.edit'),
    ]);
})->name('well-known.passkeys');
