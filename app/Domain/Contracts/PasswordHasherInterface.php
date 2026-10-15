<?php

declare(strict_types=1);

namespace App\Domain\Contracts;

interface PasswordHasherInterface
{
    public function hash(string $senha): string;

    public function verificar(string $senha, string $hash): bool;
}
