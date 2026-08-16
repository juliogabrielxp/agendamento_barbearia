<?php

declare(strict_types=1);

namespace App\Application\UseCases;

final class AutenticarComGoogleInput
{
    public function __construct(
        public readonly string $googleId,
        public readonly string $nome,
        public readonly string $email,
        public readonly ?string $avatar
    ) {
    }
}
