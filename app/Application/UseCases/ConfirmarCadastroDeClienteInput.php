<?php

declare(strict_types=1);

namespace App\Application\UseCases;

final class ConfirmarCadastroDeClienteInput
{
    public function __construct(
        public readonly string $email,
        public readonly string $chave,
        public readonly string $codigo
    ) {}
}
