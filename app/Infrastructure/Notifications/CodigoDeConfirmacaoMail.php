<?php

declare(strict_types=1);

namespace App\Infrastructure\Notifications;

use App\Domain\CadastroPendente;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

final class CodigoDeConfirmacaoMail extends Mailable
{
    public function __construct(
        public readonly string $nome,
        public readonly string $codigo,
        public readonly string $nomeDoEstabelecimento
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "{$this->codigo} é seu código no {$this->nomeDoEstabelecimento}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.codigo-de-confirmacao',
            text: 'emails.codigo-de-confirmacao-texto',
            with: ['validadeEmMinutos' => CadastroPendente::VALIDADE_DO_CODIGO_EM_MINUTOS],
        );
    }
}
