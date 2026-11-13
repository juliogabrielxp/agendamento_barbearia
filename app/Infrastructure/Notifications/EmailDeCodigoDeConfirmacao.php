<?php

declare(strict_types=1);

namespace App\Infrastructure\Notifications;

use App\Domain\Contracts\EnviadorDeCodigoDeConfirmacaoInterface;
use App\Domain\Exceptions\EnvioDeEmailFalhouException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Envia na hora, sem fila: a pessoa está olhando a tela esperando o código,
 * e se o envio falhar ela precisa saber agora para tentar de novo.
 */
final class EmailDeCodigoDeConfirmacao implements EnviadorDeCodigoDeConfirmacaoInterface
{
    public function enviar(string $email, string $nome, string $codigo, string $nomeDoEstabelecimento): void
    {
        try {
            Mail::to($email, $nome)->send(new CodigoDeConfirmacaoMail($nome, $codigo, $nomeDoEstabelecimento));
        } catch (Throwable $e) {
            Log::error('Falha ao enviar código de confirmação de cadastro', ['erro' => $e->getMessage()]);

            throw EnvioDeEmailFalhouException::porCausaDe($e);
        }
    }
}
