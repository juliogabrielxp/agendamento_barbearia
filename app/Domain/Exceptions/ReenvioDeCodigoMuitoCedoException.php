<?php

declare(strict_types=1);

namespace App\Domain\Exceptions;

use DomainException;

class ReenvioDeCodigoMuitoCedoException extends DomainException
{
    public function __construct(public readonly int $segundosRestantes)
    {
        parent::__construct("Aguarde {$segundosRestantes} segundos para pedir outro código.");
    }

    public static function aguarde(int $segundosRestantes): self
    {
        return new self($segundosRestantes);
    }
}
