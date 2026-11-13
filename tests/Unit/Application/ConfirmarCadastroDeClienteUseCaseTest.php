<?php

declare(strict_types=1);

namespace Tests\Unit\Application;

use App\Application\UseCases\ConfirmarCadastroDeClienteInput;
use App\Application\UseCases\ConfirmarCadastroDeClienteUseCase;
use App\Domain\CadastroPendente;
use App\Domain\Contracts\CadastroPendenteRepositoryInterface;
use App\Domain\Contracts\ClienteRepositoryInterface;
use App\Domain\Contracts\PasswordHasherInterface;
use App\Domain\Contracts\RelogioInterface;
use App\Domain\Contracts\TransacaoInterface;
use App\Domain\Contracts\UserRepositoryInterface;
use App\Domain\Exceptions\ConfirmacaoDeEmailException;
use App\Domain\Exceptions\EmailJaCadastradoException;
use DateTimeImmutable;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ConfirmarCadastroDeClienteUseCaseTest extends TestCase
{
    private UserRepositoryInterface&MockObject $usuarios;

    private ClienteRepositoryInterface&MockObject $clientes;

    private CadastroPendenteRepositoryInterface&MockObject $pendentes;

    private DateTimeImmutable $agora;

    protected function setUp(): void
    {
        $this->usuarios = $this->createMock(UserRepositoryInterface::class);
        $this->clientes = $this->createMock(ClienteRepositoryInterface::class);
        $this->pendentes = $this->createMock(CadastroPendenteRepositoryInterface::class);
        $this->agora = new DateTimeImmutable('2026-11-13 10:05:00');
    }

    public function test_codigo_certo_cria_usuario_com_a_senha_do_cadastro_e_o_cliente(): void
    {
        $this->pendentes->method('travarPorEmail')->willReturn($this->cadastro());
        $this->usuarios->method('emailJaCadastrado')->willReturn(false);

        $this->usuarios->expects($this->once())
            ->method('criarComSenha')
            ->with('Maria Silva', 'maria@email.com', 'hash-da-senha')
            ->willReturn(['id' => 10, 'nome' => 'Maria Silva', 'email' => 'maria@email.com']);
        $this->clientes->expects($this->once())->method('criarParaUsuario')->with(10, 'Maria Silva', 'maria@email.com');
        $this->pendentes->expects($this->once())->method('remover')->with('maria@email.com');

        $usuario = $this->useCase()->executar(new ConfirmarCadastroDeClienteInput('maria@email.com', 'chave', '123456'));

        $this->assertSame(10, $usuario['id']);
    }

    public function test_codigo_errado_conta_a_tentativa_e_nao_cria_nada(): void
    {
        $this->pendentes->method('travarPorEmail')->willReturn($this->cadastro());
        $this->pendentes->expects($this->once())->method('registrarTentativaErrada')->with('maria@email.com');
        $this->usuarios->expects($this->never())->method('criarComSenha');

        $this->expectExceptionObject(ConfirmacaoDeEmailException::codigoIncorreto());

        $this->useCase()->executar(new ConfirmarCadastroDeClienteInput('maria@email.com', 'chave', '000000'));
    }

    public function test_chave_de_outra_sessao_e_tratada_como_cadastro_inexistente(): void
    {
        $this->pendentes->method('travarPorEmail')->willReturn($this->cadastro());
        $this->pendentes->expects($this->never())->method('registrarTentativaErrada');
        $this->usuarios->expects($this->never())->method('criarComSenha');

        $this->expectExceptionObject(ConfirmacaoDeEmailException::cadastroNaoEncontrado());

        $this->useCase()->executar(new ConfirmarCadastroDeClienteInput('maria@email.com', 'outra-chave', '123456'));
    }

    public function test_codigo_expirado_nao_cria_conta(): void
    {
        $this->pendentes->method('travarPorEmail')->willReturn($this->cadastro(iniciadoEm: '2026-11-13 09:40:00'));
        $this->usuarios->expects($this->never())->method('criarComSenha');

        $this->expectExceptionObject(ConfirmacaoDeEmailException::codigoExpirado());

        $this->useCase()->executar(new ConfirmarCadastroDeClienteInput('maria@email.com', 'chave', '123456'));
    }

    public function test_email_que_ganhou_conta_enquanto_esperava_o_codigo_nao_e_duplicado(): void
    {
        $this->pendentes->method('travarPorEmail')->willReturn($this->cadastro());
        $this->usuarios->method('emailJaCadastrado')->willReturn(true);
        $this->usuarios->expects($this->never())->method('criarComSenha');

        $this->expectException(EmailJaCadastradoException::class);

        $this->useCase()->executar(new ConfirmarCadastroDeClienteInput('maria@email.com', 'chave', '123456'));
    }

    private function cadastro(string $iniciadoEm = '2026-11-13 10:00:00'): CadastroPendente
    {
        return CadastroPendente::iniciar(
            'Maria Silva',
            'maria@email.com',
            'hash-da-senha',
            'hash(123456)',
            'chave',
            new DateTimeImmutable($iniciadoEm),
        );
    }

    private function useCase(): ConfirmarCadastroDeClienteUseCase
    {
        $hasher = $this->createStub(PasswordHasherInterface::class);
        $hasher->method('verificar')->willReturnCallback(fn (string $valor, string $hash) => $hash === "hash({$valor})");

        $transacao = $this->createStub(TransacaoInterface::class);
        $transacao->method('executar')->willReturnCallback(fn (callable $operacao) => $operacao());

        $relogio = $this->createStub(RelogioInterface::class);
        $relogio->method('agora')->willReturn($this->agora);

        return new ConfirmarCadastroDeClienteUseCase(
            $this->usuarios,
            $this->clientes,
            $this->pendentes,
            $hasher,
            $transacao,
            $relogio,
        );
    }
}
