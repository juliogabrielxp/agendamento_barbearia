<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\Contracts\ClienteRepositoryInterface;
use App\Infrastructure\Persistence\Eloquent\ClienteModel;

class EloquentClienteRepository implements ClienteRepositoryInterface
{
    public function existeParaUsuario(int $userId): bool
    {
        return ClienteModel::where('user_id', $userId)->exists();
    }

    public function criarParaUsuario(int $userId, string $nome, string $email): void
    {
        ClienteModel::create([
            'user_id' => $userId,
            'nome' => $nome,
            'email' => $email,
            'telefone' => '',
        ]);
    }
}
