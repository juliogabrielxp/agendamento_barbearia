<?php

declare(strict_types=1);

namespace Tests\Unit\Application;

use App\Application\UseCases\AutenticarComSenhaInput;
use App\Application\UseCases\AutenticarComSenhaUseCase;
use App\Domain\Contracts\BarbeariaRepositoryInterface;
use App\Domain\Contracts\ClienteRepositoryInterface;
use App\Domain\Contracts\PasswordHasherInterface;
use App\Domain\Contracts\UserRepositoryInterface;
use App\Domain\Exceptions\CredenciaisInvalidasException;
use App\Domain\TipoDeConta;
use PHPUnit\Framework\TestCase;

class AutenticarComSenhaUseCaseTest extends TestCase
{
    public function test_deve_autenticar_cliente_com_credenciais_corretas(): void
    {
        // Arrange
        $useCase = $this->criarUseCase($this->credenciais(), senhaConfere: true, ehCliente: true);

        // Act
        $usuario = $useCase->executar(new AutenticarComSenhaInput('maria@email.com', 'senha', TipoDeConta::Cliente));

        // Assert
        $this->assertSame(7, $usuario['id']);
        $this->assertArrayNotHasKey('password_hash', $usuario);
    }

    public function test_deve_autenticar_barbearia_com_credenciais_corretas(): void
    {
        // Arrange
        $useCase = $this->criarUseCase($this->credenciais(), senhaConfere: true, ehBarbearia: true);

        // Act
        $usuario = $useCase->executar(new AutenticarComSenhaInput('maria@email.com', 'senha', TipoDeConta::Barbearia));

        // Assert
        $this->assertSame(7, $usuario['id']);
    }

    public function test_nao_deve_autenticar_com_email_inexistente(): void
    {
        // Arrange
        $useCase = $this->criarUseCase(null);

        // Assert
        $this->expectException(CredenciaisInvalidasException::class);

        // Act
        $useCase->executar(new AutenticarComSenhaInput('ninguem@email.com', 'senha', TipoDeConta::Cliente));
    }

    public function test_nao_deve_autenticar_com_senha_errada(): void
    {
        // Arrange
        $useCase = $this->criarUseCase($this->credenciais(), senhaConfere: false, ehCliente: true);

        // Assert
        $this->expectException(CredenciaisInvalidasException::class);

        // Act
        $useCase->executar(new AutenticarComSenhaInput('maria@email.com', 'errada', TipoDeConta::Cliente));
    }

    public function test_nao_deve_autenticar_conta_do_google_que_nao_tem_senha(): void
    {
        // Arrange
        $useCase = $this->criarUseCase($this->credenciais(hash: null), senhaConfere: true, ehCliente: true);

        // Assert
        $this->expectException(CredenciaisInvalidasException::class);

        // Act
        $useCase->executar(new AutenticarComSenhaInput('maria@email.com', 'qualquer', TipoDeConta::Cliente));
    }

    public function test_cliente_nao_deve_entrar_como_barbearia(): void
    {
        // Arrange — senha correta, mas o usuário só tem perfil de cliente
        $useCase = $this->criarUseCase($this->credenciais(), senhaConfere: true, ehCliente: true, ehBarbearia: false);

        // Assert
        $this->expectException(CredenciaisInvalidasException::class);

        // Act
        $useCase->executar(new AutenticarComSenhaInput('maria@email.com', 'senha', TipoDeConta::Barbearia));
    }

    private function credenciais(?string $hash = 'hash-da-senha'): array
    {
        return ['id' => 7, 'nome' => 'Maria', 'email' => 'maria@email.com', 'password_hash' => $hash];
    }

    private function criarUseCase(
        ?array $credenciais,
        bool $senhaConfere = true,
        bool $ehCliente = false,
        bool $ehBarbearia = false
    ): AutenticarComSenhaUseCase {
        $usuarios = $this->createMock(UserRepositoryInterface::class);
        $usuarios->method('buscarCredenciaisPorEmail')->willReturn($credenciais);

        $clientes = $this->createMock(ClienteRepositoryInterface::class);
        $clientes->method('existeParaUsuario')->willReturn($ehCliente);

        $barbearias = $this->createMock(BarbeariaRepositoryInterface::class);
        $barbearias->method('existeParaUsuario')->willReturn($ehBarbearia);

        $hasher = $this->createMock(PasswordHasherInterface::class);
        $hasher->method('verificar')->willReturn($senhaConfere);

        return new AutenticarComSenhaUseCase($usuarios, $clientes, $barbearias, $hasher);
    }
}
