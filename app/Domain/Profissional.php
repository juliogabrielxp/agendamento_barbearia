<?php

declare(strict_types=1);

namespace App\Domain;

use InvalidArgumentException;

class Profissional
{
    public function __construct(
        private readonly int $barbeariaId,
        private readonly string $nome,
        private readonly array $servicosIds
    ) {
        if (empty($this->nome)) {
            throw new InvalidArgumentException('Nome inválido');
        }
    }

    public function nome(): string
    {
        return $this->nome;
    }

    public function realiza(int $servicoId): bool
    {
        return in_array($servicoId, $this->servicosIds, true);
    }
}
