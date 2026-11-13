<?php

declare(strict_types=1);

namespace App\Domain\Exceptions;

use DomainException;

class ConfirmacaoDeEmailException extends DomainException
{
    public static function codigoIncorreto(): self
    {
        return new self('Código incorreto. Confira o e-mail e tente de novo.');
    }

    public static function codigoExpirado(): self
    {
        return new self('Este código expirou. Peça um novo código.');
    }

    public static function tentativasEsgotadas(): self
    {
        return new self('Muitas tentativas com código errado. Peça um novo código.');
    }

    public static function cadastroNaoEncontrado(): self
    {
        return new self('Não encontramos um cadastro esperando confirmação para este e-mail. Comece o cadastro de novo.');
    }
}
