<?php

declare(strict_types=1);

namespace App\Domain\Contracts;

use App\Domain\Exceptions\EnvioDeEmailFalhouException;

interface EnviadorDeCodigoDeConfirmacaoInterface
{
    /** @throws EnvioDeEmailFalhouException */
    public function enviar(string $email, string $nome, string $codigo, string $nomeDoEstabelecimento): void;
}
