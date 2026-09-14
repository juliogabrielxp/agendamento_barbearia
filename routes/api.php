<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AgendamentoController;
use App\Http\Controllers\Auth\MeController;
use App\Http\Controllers\ServicoController;
use App\Http\Controllers\ProfissionalController;
use App\Http\Controllers\AgendaController;
use App\Http\Controllers\BarbeariaPublicaController;
use App\Http\Controllers\MeusAgendamentosController;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [MeController::class, 'show']);

    Route::apiResource('servicos', ServicoController::class)->except(['show']);
    Route::apiResource('profissionais', ProfissionalController::class)->except(['show']);

    Route::get('/agenda', [AgendaController::class, 'index']);
    Route::get('/barbearias', [BarbeariaPublicaController::class, 'index']);
    Route::get('/barbearias/{id}', [BarbeariaPublicaController::class, 'show']);
    Route::get('/meus-agendamentos', [MeusAgendamentosController::class, 'index']);

    Route::post('/agendamentos', [AgendamentoController::class, 'store']);
});
