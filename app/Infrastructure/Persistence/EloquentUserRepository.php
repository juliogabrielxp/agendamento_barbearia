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
}
