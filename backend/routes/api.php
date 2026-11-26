<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\CancelInviteController;
use App\Http\Controllers\Admin\DisableUserController;
use App\Http\Controllers\Admin\EnableUserController;
use App\Http\Controllers\Admin\ListInvitesController;
use App\Http\Controllers\Admin\ListUsersController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Auth\MeController;
use App\Http\Controllers\Auth\SetPasswordController;
use App\Http\Controllers\Invites\CheckInviteController;
use App\Http\Controllers\Invites\RegisterController;
use App\Http\Controllers\Invites\SendInviteController;
use Illuminate\Support\Facades\Route;

// API routes (prefix /api). Login and logout: docs/product/login.md. Invites: docs/product/invites.md.
// Password reset: docs/product/password-reset.md. Admin pages: docs/product/admin.md.
Route::post('/login', LoginController::class)->name('login');
Route::post('/password/set', SetPasswordController::class)->name('password.set');
Route::post('/password/forgot', ForgotPasswordController::class)->name('password.forgot');
Route::post('/invites/check', CheckInviteController::class)->name('invites.check');
Route::post('/register', RegisterController::class)->name('register');

Route::middleware('auth:sanctum')->group(function (): void {
    Route::post('/logout', LogoutController::class)->name('logout');
    Route::get('/me', MeController::class)->name('me');

    // The server checks the admin role for each admin request (docs/product/accounts.md).
    Route::middleware('admin')->prefix('admin')->name('admin.')->group(function (): void {
        Route::post('/invites', SendInviteController::class)->name('invites.store');
        Route::get('/invites', ListInvitesController::class)->name('invites.index');
        Route::delete('/invites/{invite}', CancelInviteController::class)->name('invites.destroy');
        Route::get('/users', ListUsersController::class)->name('users.index');
        Route::post('/users/{user}/disable', DisableUserController::class)->name('users.disable');
        Route::post('/users/{user}/enable', EnableUserController::class)->name('users.enable');
    });
});
