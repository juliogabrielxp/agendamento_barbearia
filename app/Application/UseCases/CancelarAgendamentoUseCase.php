<?php

declare(strict_types=1);

namespace App\Application\UseCases;

use App\Domain\Contracts\AgendamentoRepositoryInterface;

final class CancelarAgendamentoUseCase
{
    public function __construct(
        private readonly AgendamentoRepositoryInterface $repositorio
    ) {
    }

    public function executar(int $agendamentoId, int $clienteId): void
    {
        $this->repositorio->cancelar($agendamentoId, $clienteId);
    }
}
