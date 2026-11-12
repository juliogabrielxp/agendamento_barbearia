<?php

declare(strict_types=1);

namespace App\Application\UseCases;

use App\Domain\Agendamento;
use App\Domain\Contracts\AgendamentoRepositoryInterface;
use App\Domain\Contracts\TransacaoInterface;
use App\Domain\Exceptions\ConflitoDeHorarioException;

final class AgendarHorarioUseCase
{
    public function __construct(
        private readonly AgendamentoRepositoryInterface $repositorio,
        private readonly TransacaoInterface $transacao
    ) {
    }

    public function executar(AgendarHorarioInput $input): void
    {
        $novoAgendamento = new Agendamento(
            profissionalId: $input->profissionalId,
            clienteId: $input->clienteId,
            servicoId: $input->servicoId,
            inicio: $input->inicio,
            duracaoEmMinutos: $input->duracaoEmMinutos
        );

        // Ler a agenda, checar conflito e gravar precisam ser atômicos:
        // sem o lock, duas requisições simultâneas passariam pela checagem
        // e gravariam o mesmo horário.
        $this->transacao->executar(function () use ($input, $novoAgendamento): void {
            $this->repositorio->travarAgendaDoProfissional($input->profissionalId);

            $agendamentosDoDia = $this->repositorio->buscarPorProfissionalEData(
                $input->profissionalId,
                $input->inicio
            );

            foreach ($agendamentosDoDia as $agendamentoExistente) {
                if ($novoAgendamento->conflitaCom($agendamentoExistente)) {
                    throw new ConflitoDeHorarioException();
                }
            }

            $this->repositorio->salvar($novoAgendamento);
        });
    }
}
