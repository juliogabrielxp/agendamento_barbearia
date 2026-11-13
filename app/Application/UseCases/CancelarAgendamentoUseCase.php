<?php

declare(strict_types=1);

namespace App\Application\UseCases;

use App\Domain\Contracts\AgendamentoRepositoryInterface;
use App\Domain\Exceptions\AgendamentoNaoEncontradoException;
use DomainException;

final class CancelarAgendamentoUseCase
{
    public function __construct(
        private readonly AgendamentoRepositoryInterface $repositorio
    ) {}

    /**
     * @throws AgendamentoNaoEncontradoException quando o agendamento não existe ou é de outro cliente
     * @throws DomainException quando o agendamento já foi concluído
     */
    public function executar(int $agendamentoId, int $clienteId): void
    {
        $agendamento = $this->repositorio->buscarPorId($agendamentoId);

        // Agendamento de outro cliente responde igual a inexistente, para não revelar que o id existe
        if ($agendamento === null || $agendamento->clienteId() !== $clienteId) {
            throw new AgendamentoNaoEncontradoException;
        }

        $agendamento->cancelar(); // recusa agendamento já concluído

        $this->repositorio->cancelar($agendamentoId, $clienteId);
    }
}
