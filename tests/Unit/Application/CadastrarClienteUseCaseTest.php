<?php

declare(strict_types=1);

namespace Tests\Unit\Application;

use App\Application\UseCases\CadastrarClienteInput;
use App\Application\UseCases\CadastrarClienteUseCase;
use App\Domain\Contracts\ClienteRepositoryInterface;
use App\Domain\Contracts\PasswordHasherInterface;
use App\Domain\Contracts\TransacaoInterface;
use App\Domain\Contracts\UserRepositoryInterface;
use App\Domain\Exceptions\EmailJaCadastradoException;
use PHPUnit\Framework\TestCase;

class CadastrarClienteUseCaseTest extends TestCase
{
    public function test_deve_cadastrar_cliente_com_senha_criptografada(): void
    {
        // Arrange
        $usuarios = $this->createMock(UserRepositoryInterface::class);
        $clientes = $this->createMock(ClienteRepositoryInterface::class);
        $hasher = $this->createMock(PasswordHasherInterface::class);
        $transacao = $this->createMock(TransacaoInterface::class);

        $usuarios->method('emailJaCadastrado')->willReturn(false);
        $hasher->method('hash')->with('senha-segura-123')->willReturn('hash-da-senha');
        $transacao->method('executar')->willReturnCallback(fn (callable $operacao) => $operacao());

        $usuarios->expects($this->once())
            ->method('criarComSenha')
            ->with('Maria Silva', 'maria@email.com', 'hash-da-senha')
            ->willReturn(['id' => 10, 'nome' => 'Maria Silva', 'email' => 'maria@email.com']);

        $clientes->expects($this->once())
            ->method('criarParaUsuario')
            ->with(10, 'Maria Silva', 'maria@email.com');

        $useCase = new CadastrarClienteUseCase($usuarios, $clientes, $hasher, $transacao);

        // Act
        $resultado = $useCase->executar(
            new CadastrarClienteInput('Maria Silva', 'maria@email.com', 'senha-segura-123')
        );

        // Assert
        $this->assertSame(10, $resultado['id']);
    }

    public function test_nao_deve_cadastrar_quando_email_ja_esta_em_uso(): void
    {
        // Arrange
        $usuarios = $this->createMock(UserRepositoryInterface::class);
        $clientes = $this->createMock(ClienteRepositoryInterface::class);
        $hasher = $this->createMock(PasswordHasherInterface::class);
        $transacao = $this->createMock(TransacaoInterface::class);

        $usuarios->method('emailJaCadastrado')->willReturn(true);

        $usuarios->expects($this->never())->method('criarComSenha');
        $clientes->expects($this->never())->method('criarParaUsuario');

        $useCase = new CadastrarClienteUseCase($usuarios, $clientes, $hasher, $transacao);

        // Assert
        $this->expectException(EmailJaCadastradoException::class);

        // Act
        $useCase->executar(
            new CadastrarClienteInput('Maria Silva', 'maria@email.com', 'senha-segura-123')
        );
    }
}
