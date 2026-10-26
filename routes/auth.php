<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\ConfirmablePasswordController;
use App\Http\Controllers\Auth\ForcedPasswordController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use Illuminate\Support\Facades\Route;

// Eine Selbstregistrierung gibt es bewusst nicht – Konten legen Admins an.

Route::middleware('guest')->group(function () {
    Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('login', [AuthenticatedSessionController::class, 'store'])->middleware('throttle:login');

    Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])
        ->middleware('throttle:password-reset')->name('password.email');

    Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
    Route::post('reset-password', [NewPasswordController::class, 'store'])
        ->middleware('throttle:password-reset')->name('password.store');
});

Route::middleware('auth')->group(function () {
    Route::get('confirm-password', [ConfirmablePasswordController::class, 'show'])->name('password.confirm');
    Route::post('confirm-password', [ConfirmablePasswordController::class, 'store'])->middleware('throttle:6,1');

    // Vom Admin vergebenes Passwort ersetzen (siehe RequirePasswordChange)
    Route::get('password/change', [ForcedPasswordController::class, 'show'])->middleware('auth.session')->name('password.force');
    Route::put('password/change', [ForcedPasswordController::class, 'update'])->middleware(['auth.session', 'throttle:6,1'])->name('password.force.update');

    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
});
