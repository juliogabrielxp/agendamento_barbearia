<?php

declare(strict_types=1);

namespace App\Domain;

use DateTimeImmutable;

/**
 * Horário de funcionamento da barbearia em um dia, com intervalo de almoço opcional.
 * Horários no formato "HH:MM".
 */
final class Expediente
{
    public function __construct(
        private readonly string $abertura,
        private readonly string $fechamento,
        private readonly ?string $intervaloInicio = null,
        private readonly ?string $intervaloFim = null,
    ) {
    }

    public function dentroDoHorarioDeFuncionamento(DateTimeImmutable $inicio, DateTimeImmutable $fim): bool
    {
        return $inicio >= $this->noDia($inicio, $this->abertura)
            && $fim <= $this->noDia($inicio, $this->fechamento);
    }

    public function cruzaIntervalo(DateTimeImmutable $inicio, DateTimeImmutable $fim): bool
    {
        if ($this->intervaloInicio === null || $this->intervaloFim === null) {
            return false;
        }

        return $inicio < $this->noDia($inicio, $this->intervaloFim)
            && $fim > $this->noDia($inicio, $this->intervaloInicio);
    }

    private function noDia(DateTimeImmutable $dia, string $horario): DateTimeImmutable
    {
        [$hora, $minuto] = array_map('intval', explode(':', $horario));

        return $dia->setTime($hora, $minuto);
    }
}
