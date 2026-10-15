<?php

declare(strict_types=1);

namespace App\Application\UseCases;

final class CadastrarClienteInput
{
    public function __construct(
        public readonly string $nome,
        public readonly string $email,
        public readonly string $senha
    ) {
    }
}
