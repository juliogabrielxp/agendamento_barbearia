<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\UseCases\ListarHorariosDisponiveisUseCase;
use App\Infrastructure\Persistence\Eloquent\ProfissionalModel;
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

        $profissional = ProfissionalModel::with('barbearia')->findOrFail($dados['profissional_id']);
        $barbearia = $profissional->barbearia;

        $horarios = $this->useCase->executar(
            (int) $dados['profissional_id'],
            (int) $dados['duracao_em_minutos'],
            new DateTimeImmutable($dados['data']),
            substr($barbearia->hora_abertura, 0, 5),
            substr($barbearia->hora_fechamento, 0, 5),
            $barbearia->intervalo_inicio ? substr($barbearia->intervalo_inicio, 0, 5) : null,
            $barbearia->intervalo_fim ? substr($barbearia->intervalo_fim, 0, 5) : null
        );

        return response()->json(
            array_map(fn($agendamento) => $agendamento->inicio()->format('H:i'), $horarios)
        );
    }
}
