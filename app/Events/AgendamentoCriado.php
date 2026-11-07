<?php

declare(strict_types=1);

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;

final class AgendamentoCriado
{
    use Dispatchable;

    public function __construct(
        public readonly int $profissionalId,
        public readonly int $servicoId,
        public readonly int $clienteId,
        public readonly string $inicio
    ) {
    }
}
