<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\UseCases\CancelarAgendamentoUseCase;
use App\Domain\Exceptions\AgendamentoNaoEncontradoException;
use App\Infrastructure\Persistence\Eloquent\AgendamentoModel;
use App\Infrastructure\Persistence\Eloquent\ClienteModel;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MeusAgendamentosController extends Controller
{
    public function __construct(
        private readonly CancelarAgendamentoUseCase $useCase
    ) {}

    public function index(Request $request): JsonResponse
    {
        $cliente = ClienteModel::where('user_id', $request->user()->id)->firstOrFail();

        $estabelecimento = $this->estabelecimento($request);

        // O cliente vê só os horários do estabelecimento em que está navegando
        $agendamentos = AgendamentoModel::where('cliente_id', $cliente->id)
            ->whereHas('profissional', fn ($query) => $query->where('barbearia_id', $estabelecimento->id))
            ->with(['profissional', 'servico'])
            ->orderBy('inicio', 'desc')
            ->get();

        return response()->json($agendamentos);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $cliente = ClienteModel::where('user_id', $request->user()->id)->firstOrFail();

        try {
            $this->useCase->executar($id, $cliente->id);
        } catch (AgendamentoNaoEncontradoException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        } catch (DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(null, 204);
    }
}
