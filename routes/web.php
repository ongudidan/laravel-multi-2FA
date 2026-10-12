<?php

use Illuminate\Support\Facades\Route;
use Ongudidan\Multi2fa\Http\Controllers\EmailOtpController;
use Ongudidan\Multi2fa\Http\Controllers\LoginChallengeSelectionController;
use Ongudidan\Multi2fa\Http\Controllers\TwoFactorSettingsController;
use Ongudidan\Multi2fa\Http\Controllers\PasskeySettingsController;

Route::middleware(config('multi-2fa.routes.middleware', ['web']))->group(function () {
    // Challenge Selection
    Route::get('/login-challenge-selection', [LoginChallengeSelectionController::class, 'show'])
        ->name('multi-2fa.challenge.show');
    Route::post('/login-challenge-selection', [LoginChallengeSelectionController::class, 'select'])
        ->name('multi-2fa.challenge.select');

    // Email OTP
    Route::get('/email-otp-login', [EmailOtpController::class, 'show'])
        ->name('multi-2fa.email-otp.show');
    Route::post('/email-otp-login', [EmailOtpController::class, 'verify'])
        ->name('multi-2fa.email-otp.verify');
    Route::post('/email-otp-login/resend', [EmailOtpController::class, 'resend'])
        ->name('multi-2fa.email-otp.resend');

    // Authenticated User Settings
    Route::middleware(['auth'])->group(function () {
        // Two-Factor TOTP Settings
        Route::get('/settings/two-factor', [TwoFactorSettingsController::class, 'show'])
            ->name('multi-2fa.settings.two-factor.show');

        // Passkeys Settings
        Route::get('/settings/passkeys', [PasskeySettingsController::class, 'index'])
            ->name('multi-2fa.settings.passkeys.index');
        Route::delete('/settings/passkeys/{passkey}', [PasskeySettingsController::class, 'destroy'])
            ->name('multi-2fa.settings.passkeys.destroy');
    });
});
