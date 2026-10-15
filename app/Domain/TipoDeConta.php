<?php

declare(strict_types=1);

namespace App\Domain;

enum TipoDeConta: string
{
    case Cliente = 'cliente';
    case Barbearia = 'barbearia';
}
