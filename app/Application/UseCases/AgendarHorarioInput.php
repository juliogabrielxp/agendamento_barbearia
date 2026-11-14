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
        // Preço do cadastro do serviço agora: fica gravado no agendamento
        public readonly string $preco,
        public readonly DateTimeImmutable $inicio,
        public readonly bool $podeAtravessarIntervalo = false
    ) {}
}
