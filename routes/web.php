<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\GoogleAuthController;

Route::get('/auth/google/barbearia/redirect', [GoogleAuthController::class, 'redirectBarbearia'])->name('auth.google.barbearia.redirect');
Route::get('/auth/google/barbearia/callback', [GoogleAuthController::class, 'callbackBarbearia'])->name('auth.google.barbearia.callback');

Route::get('/auth/google/cliente/redirect', [GoogleAuthController::class, 'redirectCliente'])->name('auth.google.cliente.redirect');
Route::get('/auth/google/cliente/callback', [GoogleAuthController::class, 'callbackCliente'])->name('auth.google.cliente.callback');

Route::get('/login', function () {
    return redirect('/app');
})->name('login');

Route::get('/app/{any?}', function () {
    return view('spa');
})->where('any', '.*');
