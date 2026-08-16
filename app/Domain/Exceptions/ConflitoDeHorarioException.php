<?php

declare(strict_types=1);

namespace App\Domain\Exceptions;

use DomainException;

class ConflitoDeHorarioException extends DomainException
{
    public function __construct()
    {
        parent::__construct('Já existe um agendamento nesse horário para este profissional.');
    }
}
