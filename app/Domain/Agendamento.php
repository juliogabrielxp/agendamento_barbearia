<?php

declare(strict_types=1);

namespace App\Domain;

use DateTimeImmutable;
use DomainException;

class Agendamento
{
    public function __construct(
        private readonly int $profissionalId,
        private readonly int $clienteId,
        private readonly int $servicoId,
        private readonly DateTimeImmutable $inicio,
        private readonly int $duracaoEmMinutos,
        private readonly ?int $id = null,
        private string $status = 'confirmado',
        // Preço do serviço no momento da reserva ("250.00"); null em agendamento só calculado
        private readonly ?string $preco = null,
    ) {}

    public function id(): ?int
    {
        return $this->id;
    }

    public function status(): string
    {
        return $this->status;
    }

    public function profissionalId(): int
    {
        return $this->profissionalId;
    }

    public function clienteId(): int
    {
        return $this->clienteId;
    }

    public function servicoId(): int
    {
        return $this->servicoId;
    }

    public function inicio(): DateTimeImmutable
    {
        return $this->inicio;
    }

    public function duracaoEmMinutos(): int
    {
        return $this->duracaoEmMinutos;
    }

    public function preco(): ?string
    {
        return $this->preco;
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

    public function concluir(): void
    {
        if ($this->status === 'cancelado') {
            throw new DomainException('Não é possível concluir um agendamento cancelado.');
        }

        $this->status = 'concluido';
    }

    public function cancelar(): void
    {
        if ($this->status === 'concluido') {
            throw new DomainException('Não é possível cancelar um agendamento já concluído.');
        }

        $this->status = 'cancelado';
    }

    /**
     * O cliente só cancela antes do horário começar. Depois disso quem decide o que
     * aconteceu é a dona (concluído ou cancelado), para o histórico não perder atendimento.
     */
    public function cancelarPeloCliente(DateTimeImmutable $agora): void
    {
        if ($this->status === 'confirmado' && $this->inicio <= $agora) {
            throw new DomainException('Não é possível cancelar um horário que já começou.');
        }

        $this->cancelar();
    }
}
