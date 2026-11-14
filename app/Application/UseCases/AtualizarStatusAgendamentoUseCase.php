<?php

declare(strict_types=1);

namespace App\Application\UseCases;

use App\Domain\Contracts\AgendamentoRepositoryInterface;
use App\Domain\Contracts\TransacaoInterface;
use DomainException;
use InvalidArgumentException;

final class AtualizarStatusAgendamentoUseCase
{
    private const ACOES_VALIDAS = ['concluido', 'cancelado'];

    public function __construct(
        private readonly AgendamentoRepositoryInterface $repositorio,
        private readonly TransacaoInterface $transacao
    ) {}

    public function executar(int $agendamentoId, string $novoStatus): void
    {
        if (! in_array($novoStatus, self::ACOES_VALIDAS, true)) {
            throw new InvalidArgumentException('Status inválido.');
        }

        // Ler, decidir e gravar com a linha travada: um cancelamento do cliente ao
        // mesmo tempo espera, em vez de cancelar o que acabou de ser concluído
        $this->transacao->executar(function () use ($agendamentoId, $novoStatus): void {
            $agendamento = $this->repositorio->buscarPorIdTravando($agendamentoId);

            if (! $agendamento) {
                throw new DomainException('Agendamento não encontrado.');
            }

            $novoStatus === 'concluido'
                ? $agendamento->concluir()
                : $agendamento->cancelar();

            $this->repositorio->atualizarStatus($agendamentoId, $agendamento->status());
        });
    }
}
