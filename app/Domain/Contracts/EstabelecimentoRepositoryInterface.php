<?php

declare(strict_types=1);

namespace App\Domain\Contracts;

interface EstabelecimentoRepositoryInterface
{
    public function existeParaUsuario(int $userId): bool;
}
