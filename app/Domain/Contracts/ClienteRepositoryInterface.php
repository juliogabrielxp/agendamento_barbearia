<?php

declare(strict_types=1);

namespace App\Domain\Contracts;

interface ClienteRepositoryInterface
{
    public function existeParaUsuario(int $userId): bool;

    public function criarParaUsuario(int $userId, string $nome, string $email): void;
}
