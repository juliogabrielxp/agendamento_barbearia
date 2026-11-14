<?php

declare(strict_types=1);

namespace Tests\Unit\Application;

use App\Application\UseCases\AutenticarComGoogleInput;
use App\Application\UseCases\AutenticarComGoogleUseCase;
use App\Domain\Contracts\ClienteRepositoryInterface;
use App\Domain\Contracts\TransacaoInterface;
use App\Domain\Contracts\UserRepositoryInterface;
use App\Domain\Exceptions\EmailJaCadastradoException;
use PHPUnit\Framework\TestCase;

/**
 * O login com Google cria a conta e o perfil de cliente na mesma transação:
 * quem decide isso é o use case, não o controller.
 */
class AutenticarComGoogleUseCaseTest extends TestCase
{
    private const USUARIO = ['id' => 9, 'nome' => 'Maria', 'email' => 'maria@gmail.com'];

    /** Ordem das chamadas, para provar que tudo acontece dentro da transação */
    private array $chamadas = [];

    public function test_conta_nova_ganha_perfil_de_cliente_dentro_da_transacao(): void
    {
        $usuarios = $this->createStub(UserRepositoryInterface::class);
        $usuarios->method('buscarPorGoogleId')->willReturn(null);
        $usuarios->method('buscarPorEmail')->willReturn(null);
        $usuarios->method('criar')->willReturnCallback(function () {
            $this->chamadas[] = 'criar_usuario';

            return self::USUARIO;
        });

        $clientes = $this->createMock(ClienteRepositoryInterface::class);
        $clientes->method('existeParaUsuario')->with(9)->willReturn(false);
        $clientes->expects($this->once())->method('criarParaUsuario')
            ->with(9, 'Maria', 'maria@gmail.com')
            ->willReturnCallback(function () {
                $this->chamadas[] = 'criar_cliente';
            });

        $resultado = $this->useCase($usuarios, $clientes)->executar($this->input());

        $this->assertSame(self::USUARIO, $resultado);
        $this->assertSame(['inicio_transacao', 'criar_usuario', 'criar_cliente', 'fim_transacao'], $this->chamadas);
    }

    public function test_quem_ja_tem_perfil_de_cliente_nao_ganha_outro(): void
    {
        $usuarios = $this->createStub(UserRepositoryInterface::class);
        $usuarios->method('buscarPorGoogleId')->willReturn(self::USUARIO);

        $clientes = $this->createMock(ClienteRepositoryInterface::class);
        $clientes->method('existeParaUsuario')->willReturn(true);
        $clientes->expects($this->never())->method('criarParaUsuario');

        $this->useCase($usuarios, $clientes)->executar($this->input());
    }

    public function test_email_de_outra_conta_sem_verificacao_do_google_nao_cria_nada(): void
    {
        $usuarios = $this->createMock(UserRepositoryInterface::class);
        $usuarios->method('buscarPorGoogleId')->willReturn(null);
        $usuarios->method('buscarPorEmail')->willReturn(self::USUARIO);
        $usuarios->expects($this->never())->method('vincularGoogle');

        $clientes = $this->createMock(ClienteRepositoryInterface::class);
        $clientes->expects($this->never())->method('criarParaUsuario');

        $this->expectException(EmailJaCadastradoException::class);

        $this->useCase($usuarios, $clientes)->executar($this->input(emailVerificado: false));
    }

    private function input(bool $emailVerificado = true): AutenticarComGoogleInput
    {
        return new AutenticarComGoogleInput(
            googleId: 'google-1',
            nome: 'Maria',
            email: 'maria@gmail.com',
            avatar: null,
            emailVerificado: $emailVerificado,
        );
    }

    private function useCase(UserRepositoryInterface $usuarios, ClienteRepositoryInterface $clientes): AutenticarComGoogleUseCase
    {
        $transacao = new class($this->chamadas) implements TransacaoInterface
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

        return new AutenticarComGoogleUseCase($usuarios, $clientes, $transacao);
    }
}
