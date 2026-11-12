<?php

declare(strict_types=1);

namespace App\Domain\Contracts;

use App\Domain\Agendamento;

interface AgendamentoRepositoryInterface
{
    /**
     * Trava a agenda do profissional até o fim da transação atual,
     * serializando agendamentos concorrentes para o mesmo profissional.
     */
    public function travarAgendaDoProfissional(int $profissionalId): void;

    public function buscarPorProfissionalEData(int $profissionalId, \DateTimeImmutable $data): array;

    public function buscarPorId(int $agendamentoId): ?Agendamento;

    public function salvar(Agendamento $agendamento): void;

    public function atualizarStatus(int $agendamentoId, string $status): void;

    public function cancelar(int $agendamentoId, int $clienteId): void;
}
