<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('reset-password/{token}', function ($token) {
    // Puedes retornar una vista simple o solo un mensaje.
    return 'Aquí iría el formulario de reseteo de contraseña. Token: ' . $token;
})->name('password.reset');
