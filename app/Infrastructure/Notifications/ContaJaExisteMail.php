<?php

declare(strict_types=1);

namespace App\Infrastructure\Notifications;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** Vai para quem já tem conta quando alguém tenta cadastrar o mesmo e-mail de novo */
final class ContaJaExisteMail extends Mailable
{
    public function __construct(
        public readonly string $nomeDoEstabelecimento
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Você já tem conta no {$this->nomeDoEstabelecimento}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.conta-ja-existe',
            text: 'emails.conta-ja-existe-texto',
        );
    }
}
