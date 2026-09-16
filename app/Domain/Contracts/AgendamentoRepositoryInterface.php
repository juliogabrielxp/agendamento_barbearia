<?php

declare(strict_types=1);

namespace App\Domain\Contracts;

use App\Domain\Agendamento;

interface AgendamentoRepositoryInterface
{
    public function buscarPorProfissionalEData(int $profissionalId, \DateTimeImmutable $data): array;

    public function salvar(Agendamento $agendamento): void;

    public function cancelar(int $agendamentoId, int $clienteId): void;
}
