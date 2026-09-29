<?php

declare(strict_types=1);

namespace App\Domain\Contracts;

use App\Domain\Agendamento;

interface AgendamentoRepositoryInterface
{
    public function buscarPorProfissionalEData(int $profissionalId, \DateTimeImmutable $data): array;

    public function buscarPorId(int $agendamentoId): ?Agendamento;

    public function salvar(Agendamento $agendamento): void;

    public function atualizarStatus(int $agendamentoId, string $status): void;

    public function cancelar(int $agendamentoId, int $clienteId): void;
}
