<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\UseCases\ListarHorariosDisponiveisInput;
use App\Application\UseCases\ListarHorariosDisponiveisUseCase;
use App\Domain\Agendamento;
use App\Infrastructure\Persistence\Eloquent\ProfissionalModel;
use App\Infrastructure\Persistence\Eloquent\ServicoModel;
use DateTimeImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class HorarioDisponivelController extends Controller
{
    public function __construct(
        private readonly ListarHorariosDisponiveisUseCase $useCase
    ) {}

    public function index(Request $request): JsonResponse
    {
        $dados = $request->validate([
            'profissional_id' => 'required|integer',
            'servico_id' => 'required|integer',
            'data' => 'required|date_format:Y-m-d',
        ]);

        $estabelecimento = $this->estabelecimento($request);

        // Duração e regra do intervalo vêm do cadastro do serviço, nunca do request
        $servico = ServicoModel::where('barbearia_id', $estabelecimento->id)->findOrFail($dados['servico_id']);
        $profissional = ProfissionalModel::where('barbearia_id', $estabelecimento->id)->findOrFail($dados['profissional_id']);

        $horarios = $this->useCase->executar(new ListarHorariosDisponiveisInput(
            profissionalId: $profissional->id,
            servicoId: $servico->id,
            duracaoEmMinutos: $servico->duracao_em_minutos,
            podeAtravessarIntervalo: $servico->pode_atravessar_intervalo,
            data: new DateTimeImmutable($dados['data']),
        ));

        return response()->json(
            array_map(fn (Agendamento $horario) => $horario->inicio()->format('H:i'), $horarios)
        );
    }
}
