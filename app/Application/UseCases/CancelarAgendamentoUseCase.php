<?php

declare(strict_types=1);

namespace App\Application\UseCases;

use App\Domain\Contracts\AgendamentoRepositoryInterface;
use App\Domain\Contracts\RelogioInterface;
use App\Domain\Contracts\TransacaoInterface;
use App\Domain\Exceptions\AgendamentoNaoEncontradoException;
use DomainException;

final class CancelarAgendamentoUseCase
{
    public function __construct(
        private readonly AgendamentoRepositoryInterface $repositorio,
        private readonly TransacaoInterface $transacao,
        private readonly RelogioInterface $relogio
    ) {}

    /**
     * @throws AgendamentoNaoEncontradoException quando o agendamento não existe ou é de outro cliente
     * @throws DomainException quando o agendamento já foi concluído ou o horário já começou
     */
    public function executar(int $agendamentoId, int $clienteId): void
    {
        $agora = $this->relogio->agora();

        // Ler, decidir e gravar com a linha travada: se a dona concluir ao mesmo tempo,
        // um dos dois espera, e o cliente nunca cancela um atendimento já concluído
        $this->transacao->executar(function () use ($agendamentoId, $clienteId, $agora): void {
            $agendamento = $this->repositorio->buscarPorIdTravando($agendamentoId);

            // Agendamento de outro cliente responde igual a inexistente, para não revelar que o id existe
            if ($agendamento === null || $agendamento->clienteId() !== $clienteId) {
                throw new AgendamentoNaoEncontradoException;
            }

            $agendamento->cancelarPeloCliente($agora); // recusa concluído e horário que já começou

            $this->repositorio->cancelar($agendamentoId, $clienteId);
        });
    }
}
