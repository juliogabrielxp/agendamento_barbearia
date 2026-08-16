<?php

declare(strict_types=1);

namespace App\Domain\Contracts;

interface UserRepositoryInterface
{
    public function buscarPorGoogleId(string $googleId): ?array;

    public function criar(string $googleId, string $nome, string $email, ?string $avatar): array;
}
