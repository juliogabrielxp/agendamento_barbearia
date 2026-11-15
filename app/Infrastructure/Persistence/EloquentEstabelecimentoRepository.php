<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\Contracts\EstabelecimentoRepositoryInterface;
use App\Infrastructure\Persistence\Eloquent\EstabelecimentoModel;

class EloquentEstabelecimentoRepository implements EstabelecimentoRepositoryInterface
{
    public function existeParaUsuario(int $userId): bool
    {
        return EstabelecimentoModel::where('user_id', $userId)->exists();
    }
}
