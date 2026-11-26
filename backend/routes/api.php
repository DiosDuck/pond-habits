<?php

declare(strict_types=1);

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Auth\MeController;
use App\Http\Controllers\Auth\SetPasswordController;
use App\Http\Controllers\Invites\CheckInviteController;
use App\Http\Controllers\Invites\RegisterController;
use App\Http\Controllers\Invites\SendInviteController;
use Illuminate\Support\Facades\Route;

// API routes (prefix /api). Login and logout: docs/product/login.md. Invites: docs/product/invites.md.
Route::post('/login', LoginController::class)->name('login');
Route::post('/password/set', SetPasswordController::class)->name('password.set');
Route::post('/invites/check', CheckInviteController::class)->name('invites.check');
Route::post('/register', RegisterController::class)->name('register');

Route::middleware('auth:sanctum')->group(function (): void {
    Route::post('/logout', LogoutController::class)->name('logout');
    Route::get('/me', MeController::class)->name('me');

    // The server checks the admin role for each admin request (docs/product/accounts.md).
    Route::middleware('admin')->prefix('admin')->name('admin.')->group(function (): void {
        Route::post('/invites', SendInviteController::class)->name('invites.store');
    });
});
