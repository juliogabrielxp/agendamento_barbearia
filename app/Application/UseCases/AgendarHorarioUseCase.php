<?php

declare(strict_types=1);

namespace App\Application\UseCases;

use App\Domain\Agendamento;
use App\Domain\Contracts\AgendamentoRepositoryInterface;
use App\Domain\Contracts\RelogioInterface;
use App\Domain\Contracts\TransacaoInterface;
use App\Domain\Exceptions\AgendamentoInvalidoException;
use App\Domain\Exceptions\ConflitoDeHorarioException;

final class AgendarHorarioUseCase
{
    public function __construct(
        private readonly AgendamentoRepositoryInterface $repositorio,
        private readonly TransacaoInterface $transacao,
        private readonly RelogioInterface $relogio
    ) {
    }

    /**
     * @throws AgendamentoInvalidoException quando o pedido viola uma regra da barbearia
     * @throws ConflitoDeHorarioException quando o horário já está ocupado
     */
    public function executar(AgendarHorarioInput $input): void
    {
        $novoAgendamento = new Agendamento(
            profissionalId: $input->profissionalId,
            clienteId: $input->clienteId,
            servicoId: $input->servicoId,
            inicio: $input->inicio,
            duracaoEmMinutos: $input->duracaoEmMinutos
        );

        if ($novoAgendamento->inicio() <= $this->relogio->agora()) {
            throw AgendamentoInvalidoException::noPassado();
        }

        // Ler a agenda, checar conflito e gravar precisam ser atômicos:
        // sem o lock, duas requisições simultâneas passariam pela checagem
        // e gravariam o mesmo horário.
        $this->transacao->executar(function () use ($input, $novoAgendamento): void {
            $this->repositorio->travarAgendaDoProfissional($input->profissionalId);

            $this->validarRegrasDaBarbearia($novoAgendamento);

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

    private function validarRegrasDaBarbearia(Agendamento $agendamento): void
    {
        $expediente = $this->repositorio->buscarExpedienteDoProfissional($agendamento->profissionalId());

        if ($expediente === null) {
            throw AgendamentoInvalidoException::profissionalInexistente();
        }

        if (!$this->repositorio->profissionalRealizaServico($agendamento->profissionalId(), $agendamento->servicoId())) {
            throw AgendamentoInvalidoException::servicoNaoRealizadoPeloProfissional();
        }

        if (!$expediente->dentroDoHorarioDeFuncionamento($agendamento->inicio(), $agendamento->fim())) {
            throw AgendamentoInvalidoException::foraDoExpediente();
        }

        if ($expediente->cruzaIntervalo($agendamento->inicio(), $agendamento->fim())) {
            throw AgendamentoInvalidoException::noIntervalo();
        }
    }
}
