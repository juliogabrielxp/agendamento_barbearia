<?php

declare(strict_types=1);

namespace Tests\Unit\Application;

use App\Application\UseCases\AgendarHorarioUseCase;
use App\Application\UseCases\AgendarHorarioInput;
use App\Domain\Contracts\AgendamentoRepositoryInterface;
use App\Domain\Exceptions\ConflitoDeHorarioException;
use PHPUnit\Framework\TestCase;
use DateTimeImmutable;

class AgendarHorarioUseCaseTest extends TestCase
{
    public function test_deve_agendar_quando_nao_ha_conflito(): void
    {
        // Arrange
        $repositorio = $this->createMock(AgendamentoRepositoryInterface::class);

        $repositorio->method('buscarPorProfissionalEData')
            ->willReturn([]); 

        $repositorio->expects($this->once())
            ->method('salvar');

        $useCase = new AgendarHorarioUseCase($repositorio);

        $input = new AgendarHorarioInput(
            profissionalId: 1,
            clienteId: 1,
            servicoId: 1,
            duracaoEmMinutos: 30,
            inicio: new DateTimeImmutable('2026-08-10 10:00')
        );

        // Act
        $useCase->executar($input);

        // Assert — a verificação principal já está no $repositorio->expects(once)->salvar()
        $this->assertTrue(true);
    }

    public function test_nao_deve_agendar_quando_ha_conflito(): void
    {
        // Arrange
        $repositorio = $this->createMock(AgendamentoRepositoryInterface::class);

        $agendamentoExistente = new \App\Domain\Agendamento(
            profissionalId: 1,
            clienteId: 50,
            servicoId: 1,
            inicio: new DateTimeImmutable('2026-08-10 10:00'),
            duracaoEmMinutos: 30
        );

        $repositorio->method('buscarPorProfissionalEData')
            ->willReturn([$agendamentoExistente]);

        $repositorio->expects($this->never())
            ->method('salvar');

        $useCase = new AgendarHorarioUseCase($repositorio);

        $input = new AgendarHorarioInput(
            profissionalId: 1,
            clienteId: 2,
            servicoId: 1,
            duracaoEmMinutos: 30,
            inicio: new DateTimeImmutable('2026-08-10 10:15') // conflita
        );

        // Assert
        $this->expectException(ConflitoDeHorarioException::class);

        // Act
        $useCase->executar($input);
    }
}
