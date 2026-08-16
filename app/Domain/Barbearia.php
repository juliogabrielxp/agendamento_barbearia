<?php

declare(strict_types=1);

namespace App\Domain;

use InvalidArgumentException;
use DateTimeImmutable;

class Barbearia
{
    public function __construct(
        private readonly ?int $id,
        private readonly string $nome,
        private readonly string $telefone,
        private readonly string $email,
        private readonly string $endereco,
        private readonly DateTimeImmutable $createdAt = new DateTimeImmutable(),
        private ?DateTimeImmutable $updatedAt = null
    ) {
        if (empty($this->nome)) {
            throw new InvalidArgumentException('Nome inválido');
        }

        if (!filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('E-mail inválido');
        }

        if (empty($this->endereco)) {
            throw new InvalidArgumentException('Endereço inválido');
        }
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function nome(): string
    {
        return $this->nome;
    }

    public function email(): string
    {
        return $this->email;
    }
}
