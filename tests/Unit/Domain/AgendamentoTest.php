<?php

declare(strict_types=1);

namespace Tests\Unit\Domain;

use App\Domain\Agendamento;
use PHPUnit\Framework\TestCase;
use DateTimeImmutable;

class AgendamentoTest extends TestCase
{
    public function test_deve_detectar_conflito_quando_horarios_se_sobrepoem(): void
    {
        // Arrange
        $profissionalId = 1;
        $servicoId = 10;
        $clienteId = 3;

        $agendamentoA = new Agendamento(
            profissionalId: $profissionalId,
            clienteId: $clienteId,
            servicoId: $servicoId,
            inicio: new DateTimeImmutable('2026-08-10 10:00'),
            duracaoEmMinutos: 30
        );

        $agendamentoB = new Agendamento(
            profissionalId: $profissionalId,
            clienteId: $clienteId,
            servicoId: $servicoId,
            inicio: new DateTimeImmutable('2026-08-10 10:15'),
            duracaoEmMinutos: 30
        );

        // Act
        $existeConflito = $agendamentoA->conflitaCom($agendamentoB);

        // Assert
        $this->assertTrue($existeConflito);
    }

    public function test_nao_deve_detectar_conflito_quando_horarios_nao_se_sobrepoem() :void
    {

        $profissionalId = 1;
        $servicoId = 10;
        $clienteId = 3;

        $agendamentoA = new Agendamento(
            profissionalId: $profissionalId,
            clienteId: $clienteId,
            servicoId: $servicoId,
            inicio: new DateTimeImmutable('2026-07-08 14:00'),
            duracaoEmMinutos: 40
        );

        $agendamentoB = new Agendamento(
            profissionalId: $profissionalId,
            clienteId: $clienteId,
            servicoId: $servicoId,
            inicio: new DateTimeImmutable('2026-07-08 15:00'),
            duracaoEmMinutos: 40
        );

        $existeConflito = $agendamentoA->conflitaCom($agendamentoB);

        $this->assertFalse($existeConflito);
    }

    public function test_nao_deve_detectar_conflito_quando_profissionais_sao_diferentes() :void
    {


        $agendamentoA = new Agendamento(
            profissionalId: 1,
            clienteId: 10,
            servicoId: 20,
            inicio: new DateTimeImmutable('2026-07-08 14:00'),
            duracaoEmMinutos: 40
        );

        $agendamentoB = new Agendamento(
            profissionalId: 2,
            clienteId: 10,
            servicoId: 20,
            inicio: new DateTimeImmutable('2026-07-08 14:00'),
            duracaoEmMinutos: 40
        );

        $existeConflito = $agendamentoA->conflitaCom($agendamentoB);

        $this->assertFalse($existeConflito);
    }

    public function test_nao_deve_detectar_conflito_quando_um_termina_exatamente_quando_outro_comeca() :void
    {
        $profissionalId = 1;
        $servicoId = 10;
        $clienteId = 3;

        $agendamentoA = new Agendamento(
            profissionalId: $profissionalId,
            clienteId: $clienteId,
            servicoId: $servicoId,
            inicio: new DateTimeImmutable('2026-07-08 14:00'),
            duracaoEmMinutos: 40
        );

        $agendamentoB = new Agendamento(
            profissionalId: $profissionalId,
            clienteId: $clienteId,
            servicoId: $servicoId,
            inicio: new DateTimeImmutable('2026-07-08 14:40'),
            duracaoEmMinutos: 40
        );

        $existeConflito = $agendamentoA->conflitaCom($agendamentoB);

        $this->assertFalse($existeConflito);
    }
}
