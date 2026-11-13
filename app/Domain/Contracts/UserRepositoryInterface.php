<?php

declare(strict_types=1);

namespace App\Domain\Contracts;

interface UserRepositoryInterface
{
    public function buscarPorGoogleId(string $googleId): ?array;

    public function buscarPorEmail(string $email): ?array;

    /** Liga uma conta Google a um usuário que já existe (ex.: cadastrado com senha) */
    public function vincularGoogle(int $userId, string $googleId, ?string $avatar): array;

    public function criar(string $googleId, string $nome, string $email, ?string $avatar): array;

    /** @return array{id: int, nome: string, email: string, password_hash: ?string}|null */
    public function buscarCredenciaisPorEmail(string $email): ?array;

    public function emailJaCadastrado(string $email): bool;

    public function criarComSenha(string $nome, string $email, string $senhaHash): array;
}
