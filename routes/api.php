<?php

use App\Http\Controllers\AgendaController;
use App\Http\Controllers\AgendamentoController;
use App\Http\Controllers\Auth\MeController;
use App\Http\Controllers\BarbeariaConfiguracaoController;
use App\Http\Controllers\BarbeariaPerfilController;
use App\Http\Controllers\EstabelecimentoPublicoController;
use App\Http\Controllers\HorarioDisponivelController;
use App\Http\Controllers\ImagemController;
use App\Http\Controllers\LogoutController;
use App\Http\Controllers\MeusAgendamentosController;
use App\Http\Controllers\ProfissionalController;
use App\Http\Controllers\PushAssinaturaController;
use App\Http\Controllers\ServicoController;
use Illuminate\Support\Facades\Route;

// Vitrine: aberta sem login, no estabelecimento do host
Route::middleware(['estabelecimento', 'throttle:60,1'])->group(function () {
    Route::get('/estabelecimento', [EstabelecimentoPublicoController::class, 'show']);
    Route::get('/horarios-disponiveis', [HorarioDisponivelController::class, 'index']);
});

// Cliente logado, sempre dentro do estabelecimento do host
Route::middleware(['auth:sanctum', 'estabelecimento'])->group(function () {
    Route::post('/agendamentos', [AgendamentoController::class, 'store']);
    Route::get('/meus-agendamentos', [MeusAgendamentosController::class, 'index']);
    Route::delete('/meus-agendamentos/{id}', [MeusAgendamentosController::class, 'destroy']);
});

// Painel da dona: tudo filtrado pelo estabelecimento do usuário logado
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [MeController::class, 'show']);

    Route::apiResource('servicos', ServicoController::class)->except(['show']);
    Route::apiResource('profissionais', ProfissionalController::class)->except(['show']);

    Route::get('/agenda', [AgendaController::class, 'index']);

    Route::get('/configuracao-barbearia', [BarbeariaConfiguracaoController::class, 'show']);

    Route::put('/configuracao-barbearia', [BarbeariaConfiguracaoController::class, 'update']);

    Route::post('/logout', [LogoutController::class, 'logout']);

    Route::patch('/agenda/{id}/status', [AgendaController::class, 'atualizarStatus']);

    Route::get('/perfil-barbearia', [BarbeariaPerfilController::class, 'show']);

    Route::put('/perfil-barbearia', [BarbeariaPerfilController::class, 'update']);

    Route::post('/perfil-barbearia/imagem', [ImagemController::class, 'barbearia']);

    Route::post('/servicos/{id}/imagem', [ImagemController::class, 'servico']);

    Route::get('/push/chave-publica', [PushAssinaturaController::class, 'chavePublica']);

    Route::post('/push/assinaturas', [PushAssinaturaController::class, 'store']);

    Route::delete('/push/assinaturas', [PushAssinaturaController::class, 'destroy']);
});
