<?php

declare(strict_types=1);

namespace App\Application\UseCases;

use DateTimeImmutable;

final class AgendarHorarioInput
{
    public function __construct(
        public readonly int $profissionalId,
        public readonly int $clienteId,
        public readonly int $servicoId,
        public readonly int $duracaoEmMinutos,
        public readonly DateTimeImmutable $inicio
    ) {
    }
}
