<?php

declare(strict_types=1);

namespace Tests\Unit\Application;

use App\Application\UseCases\AgendarHorarioUseCase;
use App\Application\UseCases\AgendarHorarioInput;
use App\Domain\Contracts\AgendamentoRepositoryInterface;
use App\Domain\Contracts\RelogioInterface;
use App\Domain\Expediente;
use App\Domain\Contracts\TransacaoInterface;
use App\Domain\Exceptions\ConflitoDeHorarioException;
use PHPUnit\Framework\TestCase;
use DateTimeImmutable;

class AgendarHorarioUseCaseTest extends TestCase
{
    /** Registra a ordem das chamadas para provar que tudo acontece dentro da transação */
    private array $chamadas = [];

    public function test_deve_agendar_quando_nao_ha_conflito(): void
    {
        // Arrange
        $repositorio = $this->repositorioQueRegistra(agendamentosDoDia: []);

        $repositorio->expects($this->once())
            ->method('salvar')
            ->willReturnCallback(function () {
                $this->chamadas[] = 'salvar';
            });

        $useCase = new AgendarHorarioUseCase($repositorio, $this->transacaoQueRegistra(), $this->relogioParado());

        // Act
        $useCase->executar($this->input('2026-08-10 10:00'));

        // Assert: trava antes de ler, e ler/gravar dentro da mesma transação
        $this->assertSame(
            ['inicio_transacao', 'travar', 'buscar', 'salvar', 'fim_transacao'],
            $this->chamadas
        );
    }

    public function test_nao_deve_agendar_quando_ha_conflito(): void
    {
        // Arrange
        $agendamentoExistente = new \App\Domain\Agendamento(
            profissionalId: 1,
            clienteId: 50,
            servicoId: 1,
            inicio: new DateTimeImmutable('2026-08-10 10:00'),
            duracaoEmMinutos: 30
        );

        $repositorio = $this->repositorioQueRegistra(agendamentosDoDia: [$agendamentoExistente]);

        $repositorio->expects($this->never())
            ->method('salvar');

        $useCase = new AgendarHorarioUseCase($repositorio, $this->transacaoQueRegistra(), $this->relogioParado());

        // Assert
        $this->expectException(ConflitoDeHorarioException::class);

        // Act
        $useCase->executar($this->input('2026-08-10 10:15')); // conflita
    }

    private function repositorioQueRegistra(array $agendamentosDoDia): AgendamentoRepositoryInterface
    {
        $repositorio = $this->createMock(AgendamentoRepositoryInterface::class);

        $repositorio->expects($this->once())
            ->method('travarAgendaDoProfissional')
            ->with(1)
            ->willReturnCallback(function () {
                $this->chamadas[] = 'travar';
            });

        // Regras da barbearia satisfeitas: o foco destes testes é o conflito
        $repositorio->method('buscarExpedienteDoProfissional')->willReturn(new Expediente('08:00', '19:00'));
        $repositorio->method('profissionalRealizaServico')->willReturn(true);

        $repositorio->method('buscarPorProfissionalEData')
            ->willReturnCallback(function () use ($agendamentosDoDia) {
                $this->chamadas[] = 'buscar';
                return $agendamentosDoDia;
            });

        return $repositorio;
    }

    private function transacaoQueRegistra(): TransacaoInterface
    {
        return new class ($this->chamadas) implements TransacaoInterface {
            public function __construct(private array &$chamadas)
            {
            }

            public function executar(callable $operacao): mixed
            {
                $this->chamadas[] = 'inicio_transacao';
                $resultado = $operacao();
                $this->chamadas[] = 'fim_transacao';

                return $resultado;
            }
        };
    }

    private function relogioParado(): RelogioInterface
    {
        return new class implements RelogioInterface {
            public function agora(): DateTimeImmutable
            {
                return new DateTimeImmutable('2026-08-01 09:00');
            }
        };
    }

    private function input(string $inicio): AgendarHorarioInput
    {
        return new AgendarHorarioInput(
            profissionalId: 1,
            clienteId: 2,
            servicoId: 1,
            duracaoEmMinutos: 30,
            inicio: new DateTimeImmutable($inicio)
        );
    }
}
