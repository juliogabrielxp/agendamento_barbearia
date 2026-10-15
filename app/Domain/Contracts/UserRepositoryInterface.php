<?php

declare(strict_types=1);

namespace App\Domain\Contracts;

interface UserRepositoryInterface
{
    public function buscarPorGoogleId(string $googleId): ?array;

    public function criar(string $googleId, string $nome, string $email, ?string $avatar): array;

    /** @return array{id: int, nome: string, email: string, password_hash: ?string}|null */
    public function buscarCredenciaisPorEmail(string $email): ?array;

    public function emailJaCadastrado(string $email): bool;

    public function criarComSenha(string $nome, string $email, string $senhaHash): array;
}
