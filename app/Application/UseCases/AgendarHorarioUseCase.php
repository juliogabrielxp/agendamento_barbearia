<?php

declare(strict_types=1);

namespace App\Application\UseCases;

use App\Domain\Agendamento;
use App\Domain\Contracts\AgendamentoRepositoryInterface;
use App\Domain\Exceptions\ConflitoDeHorarioException;

final class AgendarHorarioUseCase
{
    public function __construct(
        private readonly AgendamentoRepositoryInterface $repositorio
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
    }
}
