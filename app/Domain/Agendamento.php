<?php

declare(strict_types=1);

namespace App\Domain;

use DateTimeImmutable;

class Agendamento
{
    public function __construct(
        private readonly int $profissionalId,
        private readonly int $clienteId,
        private readonly int $servicoId,
        private readonly DateTimeImmutable $inicio,
        private readonly int $duracaoEmMinutos
    ) {
    }

    public function profissionalId() :int
    {
        return $this->profissionalId;
    }

    public function clienteId() :int
    {
        return $this->clienteId;
    }

    public function servicoId() :int
    {
        return $this->servicoId;
    }

    public function inicio(): DateTimeImmutable
    {
    return $this->inicio;
    }

    public function fim(): DateTimeImmutable
    {
        return $this->inicio->modify("+{$this->duracaoEmMinutos} minutes");
    }

    public function conflitaCom(Agendamento $outro): bool
    {
        if ($this->profissionalId !== $outro->profissionalId) {
            return false;
        }

        return $this->inicio < $outro->fim() && $this->fim() > $outro->inicio;
    }


}
