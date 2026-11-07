<?php

declare(strict_types=1);

namespace App\Domain\Contracts;

interface NotificadorDePushInterface
{
    /** Retorna quantos aparelhos do usuário receberam a notificação. */
    public function enviarParaUsuario(int $userId, string $titulo, string $mensagem, string $url): int;
}
