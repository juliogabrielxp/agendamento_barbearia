<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\UseCases\AtualizarStatusAgendamentoUseCase;
use App\Infrastructure\Persistence\Eloquent\AgendamentoModel;
use App\Infrastructure\Persistence\Eloquent\ProfissionalModel;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;

class AgendaController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'data' => 'nullable|date_format:Y-m-d',
        ]);

        $estabelecimento = $this->estabelecimentoDoDono($request);
        $dia = Carbon::createFromFormat('!Y-m-d', $request->query('data') ?? now()->format('Y-m-d'));
        $profissionaisIds = ProfissionalModel::where('estabelecimento_id', $estabelecimento->id)->pluck('id');

        // Intervalo em vez de whereDate: DATE(inicio) impediria o índice (profissional_id, inicio)
        $agendamentos = AgendamentoModel::whereIn('profissional_id', $profissionaisIds)
            ->where('inicio', '>=', $dia->format('Y-m-d H:i:s'))
            ->where('inicio', '<', $dia->copy()->addDay()->format('Y-m-d H:i:s'))
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

        $estabelecimento = $this->estabelecimentoDoDono($request);
        $profissionaisIds = ProfissionalModel::where('estabelecimento_id', $estabelecimento->id)->pluck('id');

        $agendamento = AgendamentoModel::whereIn('profissional_id', $profissionaisIds)->findOrFail($id);

        try {
            $useCase->executar($id, $request->input('status'));
        } catch (DomainException $e) {
            return response()->json(['message' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return response()->json(['message' => 'Status atualizado com sucesso']);
    }
}
