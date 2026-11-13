<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\UseCases\AgendarHorarioInput;
use App\Application\UseCases\AgendarHorarioUseCase;
use App\Domain\Exceptions\AgendamentoInvalidoException;
use App\Domain\Exceptions\ConflitoDeHorarioException;
use App\Events\AgendamentoCriado;
use App\Infrastructure\Persistence\Eloquent\ClienteModel;
use App\Infrastructure\Persistence\Eloquent\ProfissionalModel;
use App\Infrastructure\Persistence\Eloquent\ServicoModel;
use DateTimeImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class AgendamentoController extends Controller
{
    public function __construct(
        private readonly AgendarHorarioUseCase $useCase
    ) {}

    public function store(Request $request): JsonResponse
    {
        $cliente = ClienteModel::where('user_id', $request->user()->id)->firstOrFail();

        $dados = $request->validate([
            'profissional_id' => 'required|integer',
            'servico_id' => 'required|integer',
            'inicio' => 'required|date',
        ]);

        $estabelecimento = $this->estabelecimento($request);

        // Serviço e profissional precisam ser do estabelecimento do host
        $servico = ServicoModel::where('barbearia_id', $estabelecimento->id)->find($dados['servico_id']);
        if ($servico === null) {
            return response()->json(['erro' => AgendamentoInvalidoException::servicoInexistente()->getMessage()], 422);
        }

        $profissionalDoEstabelecimento = ProfissionalModel::where('barbearia_id', $estabelecimento->id)
            ->whereKey($dados['profissional_id'])
            ->exists();
        if (! $profissionalDoEstabelecimento) {
            return response()->json(['erro' => AgendamentoInvalidoException::profissionalInexistente()->getMessage()], 422);
        }

        // A duração e a regra do intervalo sempre vêm do cadastro do serviço, nunca do cliente
        $input = new AgendarHorarioInput(
            profissionalId: $dados['profissional_id'],
            clienteId: $cliente->id,
            servicoId: $servico->id,
            duracaoEmMinutos: $servico->duracao_em_minutos,
            inicio: new DateTimeImmutable($dados['inicio']),
            podeAtravessarIntervalo: $servico->pode_atravessar_intervalo
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
