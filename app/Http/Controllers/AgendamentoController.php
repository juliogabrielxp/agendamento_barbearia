<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\UseCases\AgendarHorarioUseCase;
use App\Application\UseCases\AgendarHorarioInput;
use App\Domain\Exceptions\AgendamentoInvalidoException;
use App\Domain\Exceptions\ConflitoDeHorarioException;
use App\Infrastructure\Persistence\Eloquent\ClienteModel;
use App\Infrastructure\Persistence\Eloquent\ServicoModel;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use DateTimeImmutable;
use App\Events\AgendamentoCriado;

class AgendamentoController extends Controller
{
    public function __construct(
        private readonly AgendarHorarioUseCase $useCase
    ) {
    }

    public function store(Request $request): JsonResponse
    {
        $cliente = ClienteModel::where('user_id', $request->user()->id)->firstOrFail();

        $dados = $request->validate([
            'profissional_id' => 'required|integer',
            'servico_id' => 'required|integer|exists:servicos,id',
            'inicio' => 'required|date',
        ]);

        // A duração sempre vem do cadastro do serviço, nunca do cliente
        $servico = ServicoModel::findOrFail($dados['servico_id']);

        $input = new AgendarHorarioInput(
            profissionalId: $dados['profissional_id'],
            clienteId: $cliente->id,
            servicoId: $servico->id,
            duracaoEmMinutos: $servico->duracao_em_minutos,
            inicio: new DateTimeImmutable($dados['inicio'])
        );

        try {
            $this->useCase->executar($input);
        } catch (ConflitoDeHorarioException $e) {
            return response()->json(['erro' => $e->getMessage()], 409);
        } catch (AgendamentoInvalidoException $e) {
            return response()->json(['erro' => $e->getMessage()], 422);
        }

        AgendamentoCriado::dispatch(
            profissionalId: $dados['profissional_id'],
            servicoId: $dados['servico_id'],
            clienteId: $cliente->id,
            inicio: $input->inicio->format('Y-m-d H:i:s'),
        );

        return response()->json(['mensagem' => 'Agendamento realizado com sucesso'], 201);
    }
}
