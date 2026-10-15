<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\Contracts\UserRepositoryInterface;
use App\Infrastructure\Persistence\Eloquent\UserModel;

class EloquentUserRepository implements UserRepositoryInterface
{
    public function buscarPorGoogleId(string $googleId): ?array
    {
        $user = UserModel::where('google_id', $googleId)->first();

        return $user ? $user->toArray() : null;
    }

    public function criar(string $googleId, string $nome, string $email, ?string $avatar): array
    {
        $user = UserModel::create([
            'google_id' => $googleId,
            'nome' => $nome,
            'email' => $email,
            'avatar' => $avatar,
        ]);

        return $user->toArray();
    }

    public function buscarCredenciaisPorEmail(string $email): ?array
    {
        $user = UserModel::where('email', $email)->first();

        if (!$user) {
            return null;
        }

        return [
            'id' => $user->id,
            'nome' => $user->nome,
            'email' => $user->email,
            'password_hash' => $user->password,
        ];
    }

    public function emailJaCadastrado(string $email): bool
    {
        return UserModel::where('email', $email)->exists();
    }

    public function criarComSenha(string $nome, string $email, string $senhaHash): array
    {
        $user = UserModel::create([
            'nome' => $nome,
            'email' => $email,
            'password' => $senhaHash,
        ]);

        return $user->toArray();
    }
}
