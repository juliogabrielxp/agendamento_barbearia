<?php

declare(strict_types=1);

namespace App\Domain;

use DateTimeImmutable;

/**
 * Horário de funcionamento do estabelecimento em um dia, com intervalo opcional.
 * Horários no formato "HH:MM".
 */
final class Expediente
{
    public function __construct(
        private readonly string $abertura,
        private readonly string $fechamento,
        private readonly ?string $intervaloInicio = null,
        private readonly ?string $intervaloFim = null,
    ) {}

    public function aberturaNoDia(DateTimeImmutable $dia): DateTimeImmutable
    {
        return $this->noDia($dia, $this->abertura);
    }

    public function fechamentoNoDia(DateTimeImmutable $dia): DateTimeImmutable
    {
        return $this->noDia($dia, $this->fechamento);
    }

    public function dentroDoHorarioDeFuncionamento(DateTimeImmutable $inicio, DateTimeImmutable $fim): bool
    {
        return $inicio >= $this->aberturaNoDia($inicio)
            && $fim <= $this->fechamentoNoDia($inicio);
    }

    public function cruzaIntervalo(DateTimeImmutable $inicio, DateTimeImmutable $fim): bool
    {
        if (! $this->temIntervalo()) {
            return false;
        }

        return $inicio < $this->noDia($inicio, $this->intervaloFim)
            && $fim > $this->noDia($inicio, $this->intervaloInicio);
    }

    public function comecaNoIntervalo(DateTimeImmutable $inicio): bool
    {
        if (! $this->temIntervalo()) {
            return false;
        }

        return $inicio >= $this->noDia($inicio, $this->intervaloInicio)
            && $inicio < $this->noDia($inicio, $this->intervaloFim);
    }

    /**
     * Serviço comum não pode encostar no intervalo. Serviço que pode atravessar
     * (ex.: trança de várias horas) só não pode começar dentro dele.
     */
    public function bloqueadoPeloIntervalo(
        DateTimeImmutable $inicio,
        DateTimeImmutable $fim,
        bool $podeAtravessarIntervalo
    ): bool {
        return $podeAtravessarIntervalo
            ? $this->comecaNoIntervalo($inicio)
            : $this->cruzaIntervalo($inicio, $fim);
    }

    private function temIntervalo(): bool
    {
        return $this->intervaloInicio !== null && $this->intervaloFim !== null;
    }

    private function noDia(DateTimeImmutable $dia, string $horario): DateTimeImmutable
    {
        [$hora, $minuto] = array_map('intval', explode(':', $horario));

        return $dia->setTime($hora, $minuto);
    }
}
