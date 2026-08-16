<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AgendamentoController;

Route::post('/agendamentos', [AgendamentoController::class, 'store']);
