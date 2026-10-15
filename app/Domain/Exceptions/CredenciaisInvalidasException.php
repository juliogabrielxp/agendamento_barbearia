<?php

declare(strict_types=1);

namespace App\Domain\Exceptions;

use DomainException;

class CredenciaisInvalidasException extends DomainException
{
    public function __construct()
    {
        parent::__construct('E-mail ou senha inválidos.');
    }
}
