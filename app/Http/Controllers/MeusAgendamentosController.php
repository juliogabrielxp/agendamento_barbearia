<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Infrastructure\Persistence\Eloquent\AgendamentoModel;
use App\Infrastructure\Persistence\Eloquent\ClienteModel;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class MeusAgendamentosController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $cliente = ClienteModel::where('user_id', $request->user()->id)->firstOrFail();

        $agendamentos = AgendamentoModel::where('cliente_id', $cliente->id)
            ->with(['profissional.barbearia', 'servico'])
            ->orderBy('inicio', 'desc')
            ->get();

        return response()->json($agendamentos);
    }
}
