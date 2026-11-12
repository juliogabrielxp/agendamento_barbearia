<?php

declare(strict_types=1);

namespace App\Domain\Contracts;

use DateTimeImmutable;

interface RelogioInterface
{
    public function agora(): DateTimeImmutable;
}
