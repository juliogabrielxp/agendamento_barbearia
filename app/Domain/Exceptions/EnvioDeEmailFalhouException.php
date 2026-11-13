<?php

declare(strict_types=1);

namespace App\Domain\Exceptions;

use RuntimeException;
use Throwable;

class EnvioDeEmailFalhouException extends RuntimeException
{
    public static function porCausaDe(Throwable $causa): self
    {
        return new self('Não conseguimos enviar o e-mail agora. Tente de novo em instantes.', 0, $causa);
    }
}
