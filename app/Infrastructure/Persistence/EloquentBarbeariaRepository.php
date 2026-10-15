<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\Contracts\BarbeariaRepositoryInterface;
use App\Infrastructure\Persistence\Eloquent\BarbeariaModel;

class EloquentBarbeariaRepository implements BarbeariaRepositoryInterface
{
    public function existeParaUsuario(int $userId): bool
    {
        return BarbeariaModel::where('user_id', $userId)->exists();
    }
}
