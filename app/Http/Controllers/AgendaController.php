<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Infrastructure\Persistence\Eloquent\AgendamentoModel;
use App\Infrastructure\Persistence\Eloquent\BarbeariaModel;
use App\Infrastructure\Persistence\Eloquent\ProfissionalModel;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AgendaController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $barbearia = BarbeariaModel::where('user_id', $request->user()->id)->firstOrFail();
        $data = $request->query('data', now()->format('Y-m-d'));
        $profissionaisIds = ProfissionalModel::where('barbearia_id', $barbearia->id)->pluck('id');

        $agendamentos = AgendamentoModel::whereIn('profissional_id', $profissionaisIds)
            ->whereDate('inicio', $data)
            ->with(['profissional', 'cliente', 'servico'])
            ->orderBy('inicio')
            ->get();

        return response()->json($agendamentos);
    }
}
