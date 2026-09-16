<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\UseCases\ListarHorariosDisponiveisUseCase;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use DateTimeImmutable;

class HorarioDisponivelController extends Controller
{
    public function __construct(
        private readonly ListarHorariosDisponiveisUseCase $useCase
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $dados = $request->validate([
            'profissional_id' => 'required|integer',
            'duracao_em_minutos' => 'required|integer',
            'data' => 'required|date',
        ]);

        $horarios = $this->useCase->executar(
            (int) $dados['profissional_id'],
            (int) $dados['duracao_em_minutos'],
            new DateTimeImmutable($dados['data'])
        );

        return response()->json(
            array_map(fn($agendamento) => $agendamento->inicio()->format('H:i'), $horarios)
        );
    }
}
