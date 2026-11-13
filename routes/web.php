<?php

use App\Http\Controllers\Auth\BarbeariaLoginController;
use App\Http\Controllers\Auth\ClienteAuthController;
use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\SpaController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/app');

Route::get('/auth/google/cliente/redirect', [GoogleAuthController::class, 'redirectCliente'])->name('auth.google.cliente.redirect');
Route::get('/auth/google/cliente/callback', [GoogleAuthController::class, 'callbackCliente'])->name('auth.google.cliente.callback');

Route::get('/login', function () {
    return redirect('/app/entrar');
})->name('login');

Route::post('/login-barbearia', [BarbeariaLoginController::class, 'login'])
    ->middleware(['estabelecimento', 'throttle:5,1'])
    ->name('login.barbearia');

Route::post('/cadastro-cliente', [ClienteAuthController::class, 'cadastrar'])
    ->middleware('throttle:10,1');

Route::post('/login-cliente', [ClienteAuthController::class, 'login'])
    ->middleware('throttle:5,1');

// Host sem estabelecimento cadastrado responde 404
Route::middleware('estabelecimento')->group(function () {
    Route::get('/manifest.webmanifest', [SpaController::class, 'manifest']);

    Route::get('/app/{any?}', [SpaController::class, 'show'])->where('any', '.*');
});
