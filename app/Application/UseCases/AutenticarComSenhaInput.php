<?php

declare(strict_types=1);

namespace App\Application\UseCases;

use App\Domain\TipoDeConta;

final class AutenticarComSenhaInput
{
    public function __construct(
        public readonly string $email,
        public readonly string $senha,
        public readonly TipoDeConta $tipoDeConta
    ) {
    }
}
