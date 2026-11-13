<?php

declare(strict_types=1);

namespace App\Domain\Exceptions;

use DomainException;

class AgendamentoNaoEncontradoException extends DomainException
{
    public function __construct()
    {
        parent::__construct('Agendamento não encontrado.');
    }
}
