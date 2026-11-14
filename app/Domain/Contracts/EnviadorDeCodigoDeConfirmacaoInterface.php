<?php

declare(strict_types=1);

namespace App\Domain\Contracts;

use App\Domain\Exceptions\EnvioDeEmailFalhouException;

interface EnviadorDeCodigoDeConfirmacaoInterface
{
    /** @throws EnvioDeEmailFalhouException */
    public function enviar(string $email, string $nome, string $codigo, string $nomeDoEstabelecimento): void;

    /**
     * Vai no lugar do código quando o e-mail já tem conta. Não leva o nome digitado no
     * cadastro: quem pediu pode ser um estranho, e o texto chega na caixa da dona da conta.
     *
     * @throws EnvioDeEmailFalhouException
     */
    public function avisarContaExistente(string $email, string $nomeDoEstabelecimento): void;
}
