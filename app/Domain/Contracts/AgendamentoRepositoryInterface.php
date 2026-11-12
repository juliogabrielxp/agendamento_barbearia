<?php

declare(strict_types=1);

namespace App\Domain\Contracts;

use App\Domain\Agendamento;
use App\Domain\Expediente;

interface AgendamentoRepositoryInterface
{
    /**
     * Trava a agenda do profissional até o fim da transação atual,
     * serializando agendamentos concorrentes para o mesmo profissional.
     */
    public function travarAgendaDoProfissional(int $profissionalId): void;

    /** Expediente da barbearia do profissional, ou null se o profissional não existe */
    public function buscarExpedienteDoProfissional(int $profissionalId): ?Expediente;

    public function profissionalRealizaServico(int $profissionalId, int $servicoId): bool;

    /** Agendamentos que ocupam a agenda no dia (cancelados não entram) */
    public function buscarPorProfissionalEData(int $profissionalId, \DateTimeImmutable $data): array;

    public function buscarPorId(int $agendamentoId): ?Agendamento;

    public function salvar(Agendamento $agendamento): void;

    public function atualizarStatus(int $agendamentoId, string $status): void;

    public function cancelar(int $agendamentoId, int $clienteId): void;
}
