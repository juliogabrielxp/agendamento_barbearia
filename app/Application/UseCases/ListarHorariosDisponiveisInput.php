<?php

declare(strict_types=1);

namespace App\Application\UseCases;

use DateTimeImmutable;

final class ListarHorariosDisponiveisInput
{
    public function __construct(
        public readonly int $profissionalId,
        public readonly int $servicoId,
        public readonly int $duracaoEmMinutos,
        public readonly bool $podeAtravessarIntervalo,
        public readonly DateTimeImmutable $data
    ) {}
}
