<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\UseCases\AgendarHorarioUseCase;
use App\Application\UseCases\AgendarHorarioInput;
use App\Domain\Exceptions\ConflitoDeHorarioException;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use DateTimeImmutable;

class AgendamentoController extends Controller
{
    public function __construct(
        private readonly AgendarHorarioUseCase $useCase
    ) {
    }

    public function store(Request $request): JsonResponse
    {
        $dados = $request->validate([
            'profissional_id' => 'required|integer',
            'cliente_id' => 'required|integer',
            'servico_id' => 'required|integer',
            'duracao_em_minutos' => 'required|integer',
            'inicio' => 'required|date',
        ]);

        $input = new AgendarHorarioInput(
            profissionalId: $dados['profissional_id'],
            clienteId: $dados['cliente_id'],
            servicoId: $dados['servico_id'],
            duracaoEmMinutos: $dados['duracao_em_minutos'],
            inicio: new DateTimeImmutable($dados['inicio'])
        );

        try {
            $this->useCase->executar($input);
        } catch (ConflitoDeHorarioException $e) {
            return response()->json(['erro' => $e->getMessage()], 409);
        }

        return response()->json(['mensagem' => 'Agendamento realizado com sucesso'], 201);
    }
}
