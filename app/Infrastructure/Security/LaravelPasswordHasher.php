<?php

declare(strict_types=1);

namespace App\Infrastructure\Security;

use App\Domain\Contracts\PasswordHasherInterface;
use Illuminate\Contracts\Hashing\Hasher;

class LaravelPasswordHasher implements PasswordHasherInterface
{
    public function __construct(
        private readonly Hasher $hasher
    ) {
    }

    public function hash(string $senha): string
    {
        return $this->hasher->make($senha);
    }

    public function verificar(string $senha, string $hash): bool
    {
        return $this->hasher->check($senha, $hash);
    }
}
