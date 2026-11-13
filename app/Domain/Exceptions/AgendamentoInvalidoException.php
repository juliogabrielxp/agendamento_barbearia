<?php

declare(strict_types=1);

namespace App\Domain\Exceptions;

use DomainException;

class AgendamentoInvalidoException extends DomainException
{
    public static function noPassado(): self
    {
        return new self('Não é possível agendar um horário que já passou.');
    }

    public static function foraDoExpediente(): self
    {
        return new self('O horário escolhido está fora do horário de funcionamento do estabelecimento.');
    }

    public static function noIntervalo(): self
    {
        return new self('O horário escolhido cruza o intervalo do estabelecimento.');
    }

    public static function servicoNaoRealizadoPeloProfissional(): self
    {
        return new self('Este profissional não realiza o serviço escolhido.');
    }

    public static function profissionalInexistente(): self
    {
        return new self('Profissional não encontrado.');
    }

    public static function servicoInexistente(): self
    {
        return new self('Serviço não encontrado neste estabelecimento.');
    }
}
