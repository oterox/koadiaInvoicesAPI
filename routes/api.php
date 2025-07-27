<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\ApiLoginController;
use App\Http\Controllers\Auth\ApiRegisterController;
use App\Http\Controllers\Auth\ApiForgotPasswordController;
use App\Http\Controllers\Auth\ApiResetPasswordController;

Route::middleware(['api'])->group(function () {
    // Login personalizado con Sanctum
    Route::post('/login', [ApiLoginController::class, 'login']);
    // Register personalizado con Sanctum
    Route::post('/register', [ApiRegisterController::class, 'register']);
    // Forgot Password personalizado
    Route::post('/forgot-password', [ApiForgotPasswordController::class, 'sendResetLinkEmail']);
    // Reset Password personalizado
    Route::post('/reset-password', [ApiResetPasswordController::class, 'reset']);
    // Logout personalizado (solo API)
    Route::post('/logout', function (Request $request) {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['success' => true, 'message' => 'Logged out']);
    })->middleware('auth:sanctum');
});
