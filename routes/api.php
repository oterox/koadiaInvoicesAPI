<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Http\Controllers\AuthenticatedSessionController;
use Laravel\Fortify\Http\Controllers\RegisteredUserController;
use Laravel\Fortify\Http\Controllers\PasswordResetLinkController;
use Laravel\Fortify\Http\Controllers\NewPasswordController;
use App\Http\Controllers\Auth\ApiLoginController;

Route::middleware(['api'])->group(function () {
    // Login personalizado con Sanctum
    Route::post('/login', [ApiLoginController::class, 'login']);
    // Logout
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy']);
    // Register
    Route::post('/register', [RegisteredUserController::class, 'store']);
    // Forgot Password
    Route::post('/forgot-password', [PasswordResetLinkController::class, 'store']);
    // Reset Password
    Route::post('/reset-password', [NewPasswordController::class, 'store']);
});
