<?php

declare(strict_types=1);

namespace App\Application\UseCases;

use App\Domain\Contracts\AgendamentoRepositoryInterface;
use DomainException;
use InvalidArgumentException;

final class AtualizarStatusAgendamentoUseCase
{
    private const ACOES_VALIDAS = ['concluido', 'cancelado'];

    public function __construct(
        private readonly AgendamentoRepositoryInterface $repositorio
    ) {
    }

    public function executar(int $agendamentoId, string $novoStatus): void
    {
        if (!in_array($novoStatus, self::ACOES_VALIDAS, true)) {
            throw new InvalidArgumentException('Status inválido.');
        }

        $agendamento = $this->repositorio->buscarPorId($agendamentoId);

        if (!$agendamento) {
            throw new DomainException('Agendamento não encontrado.');
        }

        $novoStatus === 'concluido'
            ? $agendamento->concluir()
            : $agendamento->cancelar();

        $this->repositorio->atualizarStatus($agendamentoId, $agendamento->status());
    }
}
