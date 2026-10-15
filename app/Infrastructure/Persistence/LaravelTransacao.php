<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\Contracts\TransacaoInterface;
use Closure;
use Illuminate\Database\ConnectionInterface;

class LaravelTransacao implements TransacaoInterface
{
    public function __construct(
        private readonly ConnectionInterface $conexao
    ) {
    }

    public function executar(callable $operacao): mixed
    {
        return $this->conexao->transaction(Closure::fromCallable($operacao));
    }
}
