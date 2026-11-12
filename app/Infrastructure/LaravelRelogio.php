<?php

declare(strict_types=1);

namespace App\Infrastructure;

use App\Domain\Contracts\RelogioInterface;
use DateTimeImmutable;

class LaravelRelogio implements RelogioInterface
{
    public function agora(): DateTimeImmutable
    {
        // now() respeita o fuso da app e o travelTo() dos testes
        return now()->toDateTimeImmutable();
    }
}
