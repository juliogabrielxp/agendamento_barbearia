<?php

declare(strict_types=1);

namespace Tests\Unit\Application;

use App\Application\UseCases\AtualizarStatusAgendamentoUseCase;
use App\Application\UseCases\CancelarAgendamentoUseCase;
use App\Domain\Agendamento;
use App\Domain\Contracts\AgendamentoRepositoryInterface;
use App\Domain\Contracts\RelogioInterface;
use App\Domain\Contracts\TransacaoInterface;
use DateTimeImmutable;
use DomainException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Concluir/cancelar pela dona e cancelar pelo cliente leem, decidem e gravam: as três
 * coisas precisam acontecer na mesma transação, com a linha travada na leitura.
 */
class MudancaDeStatusComTravaTest extends TestCase
{
    private const CLIENTE = 7;

    /** Ordem das chamadas, para provar que tudo acontece dentro da transação */
    private array $chamadas = [];

    public function test_dona_le_com_trava_e_grava_o_status_dentro_da_transacao(): void
    {
        $repositorio = $this->repositorioQueRegistra('confirmado');
        $repositorio->expects($this->once())->method('atualizarStatus')
            ->with(1, 'concluido')
            ->willReturnCallback(function () {
                $this->chamadas[] = 'gravar';
            });

        (new AtualizarStatusAgendamentoUseCase($repositorio, $this->transacaoQueRegistra()))->executar(1, 'concluido');

        $this->assertSame(['inicio_transacao', 'ler_travando', 'gravar', 'fim_transacao'], $this->chamadas);
    }

    public function test_cliente_le_com_trava_e_cancela_dentro_da_transacao(): void
    {
        $repositorio = $this->repositorioQueRegistra('confirmado');
        $repositorio->expects($this->once())->method('cancelar')
            ->with(1, self::CLIENTE)
            ->willReturnCallback(function () {
                $this->chamadas[] = 'gravar';
            });

        (new CancelarAgendamentoUseCase($repositorio, $this->transacaoQueRegistra(), $this->relogioAntesDoHorario()))->executar(1, self::CLIENTE);

        $this->assertSame(['inicio_transacao', 'ler_travando', 'gravar', 'fim_transacao'], $this->chamadas);
    }

    public function test_cliente_nao_cancela_o_que_a_leitura_travada_mostra_concluido(): void
    {
        $repositorio = $this->repositorioQueRegistra('concluido');
        $repositorio->expects($this->never())->method('cancelar');

        $this->expectException(DomainException::class);

        (new CancelarAgendamentoUseCase($repositorio, $this->transacaoQueRegistra(), $this->relogioAntesDoHorario()))->executar(1, self::CLIENTE);
    }

    private function repositorioQueRegistra(string $statusAtual): AgendamentoRepositoryInterface&MockObject
    {
        $repositorio = $this->createMock(AgendamentoRepositoryInterface::class);
        $repositorio->expects($this->never())->method('buscarPorId');
        $repositorio->expects($this->once())->method('buscarPorIdTravando')
            ->with(1)
            ->willReturnCallback(function () use ($statusAtual) {
                $this->chamadas[] = 'ler_travando';

                return new Agendamento(
                    profissionalId: 3,
                    clienteId: self::CLIENTE,
                    servicoId: 5,
                    inicio: new DateTimeImmutable('2030-01-10 10:00'),
                    duracaoEmMinutos: 30,
                    id: 1,
                    status: $statusAtual,
                );
            });

        return $repositorio;
    }

    private function relogioAntesDoHorario(): RelogioInterface
    {
        $relogio = $this->createStub(RelogioInterface::class);
        $relogio->method('agora')->willReturn(new DateTimeImmutable('2030-01-09 12:00'));

        return $relogio;
    }

    private function transacaoQueRegistra(): TransacaoInterface
    {
        return new class($this->chamadas) implements TransacaoInterface
        {
            public function __construct(private array &$chamadas) {}

            public function executar(callable $operacao): mixed
            {
                $this->chamadas[] = 'inicio_transacao';
                $resultado = $operacao();
                $this->chamadas[] = 'fim_transacao';

                return $resultado;
            }
        };
    }
}
