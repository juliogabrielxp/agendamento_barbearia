<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\UseCases\AtualizarStatusAgendamentoUseCase;
use App\Infrastructure\Persistence\Eloquent\AgendamentoModel;
use App\Infrastructure\Persistence\Eloquent\BarbeariaModel;
use App\Infrastructure\Persistence\Eloquent\ProfissionalModel;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use DomainException;
use Illuminate\Http\Response;

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

    public function atualizarStatus(Request $request, int $id, AtualizarStatusAgendamentoUseCase $useCase): JsonResponse
    {
        $request->validate([
            'status' => 'required|in:concluido,cancelado',
        ]);

        $barbearia = BarbeariaModel::where('user_id', $request->user()->id)->firstOrFail();
        $profissionaisIds = ProfissionalModel::where('barbearia_id', $barbearia->id)->pluck('id');

        $agendamento = AgendamentoModel::whereIn('profissional_id', $profissionaisIds)->findOrFail($id);

        try {
            $useCase->executar($id, $request->input('status'));
        } catch (DomainException $e) {
            return response()->json(['message' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return response()->json(['message' => 'Status atualizado com sucesso']);
    }
}
