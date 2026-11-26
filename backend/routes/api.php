<?php

declare(strict_types=1);

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Auth\MeController;
use App\Http\Controllers\Auth\SetPasswordController;
use Illuminate\Support\Facades\Route;

// API routes (prefix /api). Login and logout: docs/product/login.md.
Route::post('/login', LoginController::class)->name('login');
Route::post('/password/set', SetPasswordController::class)->name('password.set');

Route::middleware('auth:sanctum')->group(function (): void {
    Route::post('/logout', LogoutController::class)->name('logout');
    Route::get('/me', MeController::class)->name('me');
});
