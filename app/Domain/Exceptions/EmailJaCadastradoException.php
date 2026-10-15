<?php

declare(strict_types=1);

namespace App\Domain\Exceptions;

use DomainException;

class EmailJaCadastradoException extends DomainException
{
    public function __construct()
    {
        parent::__construct('Este e-mail já está cadastrado. Faça login.');
    }
}
