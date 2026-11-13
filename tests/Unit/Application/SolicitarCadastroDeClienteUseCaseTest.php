<?php

declare(strict_types=1);

namespace Tests\Unit\Application;

use App\Application\UseCases\SolicitarCadastroDeClienteInput;
use App\Application\UseCases\SolicitarCadastroDeClienteUseCase;
use App\Domain\CadastroPendente;
use App\Domain\Contracts\CadastroPendenteRepositoryInterface;
use App\Domain\Contracts\EnviadorDeCodigoDeConfirmacaoInterface;
use App\Domain\Contracts\PasswordHasherInterface;
use App\Domain\Contracts\RelogioInterface;
use App\Domain\Contracts\UserRepositoryInterface;
use App\Domain\Exceptions\EmailJaCadastradoException;
use App\Domain\Exceptions\EnvioDeEmailFalhouException;
use App\Domain\Exceptions\ReenvioDeCodigoMuitoCedoException;
use DateTimeImmutable;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class SolicitarCadastroDeClienteUseCaseTest extends TestCase
{
    private UserRepositoryInterface&MockObject $usuarios;

    private CadastroPendenteRepositoryInterface&MockObject $pendentes;

    private EnviadorDeCodigoDeConfirmacaoInterface&MockObject $enviador;

    private DateTimeImmutable $agora;

    protected function setUp(): void
    {
        $this->usuarios = $this->createMock(UserRepositoryInterface::class);
        $this->pendentes = $this->createMock(CadastroPendenteRepositoryInterface::class);
        $this->enviador = $this->createMock(EnviadorDeCodigoDeConfirmacaoInterface::class);
        $this->agora = new DateTimeImmutable('2026-11-13 10:00:00');
    }

    public function test_envia_o_codigo_e_guarda_cadastro_com_senha_e_codigo_criptografados(): void
    {
        $this->usuarios->method('emailJaCadastrado')->willReturn(false);

        $codigoEnviado = null;
        $this->enviador->expects($this->once())
            ->method('enviar')
            ->willReturnCallback(function (string $email, string $nome, string $codigo, string $estabelecimento) use (&$codigoEnviado) {
                $this->assertSame('maria@email.com', $email);
                $this->assertSame('Studio', $estabelecimento);
                $codigoEnviado = $codigo;
            });

        $salvo = null;
        $this->pendentes->expects($this->once())
            ->method('salvar')
            ->willReturnCallback(function (CadastroPendente $cadastro) use (&$salvo) {
                $salvo = $cadastro;
            });

        $chave = $this->useCase()->executar($this->input());

        $this->assertSame('hash(senha-segura-123)', $salvo->senhaHash);
        $this->assertSame("hash({$codigoEnviado})", $salvo->codigoHash);
        $this->assertTrue($salvo->pertenceA($chave));
        $this->assertSame(0, $salvo->tentativas);
    }

    public function test_nao_manda_codigo_para_email_ja_cadastrado(): void
    {
        $this->usuarios->method('emailJaCadastrado')->willReturn(true);
        $this->enviador->expects($this->never())->method('enviar');
        $this->pendentes->expects($this->never())->method('salvar');

        $this->expectException(EmailJaCadastradoException::class);

        $this->useCase()->executar($this->input());
    }

    public function test_nao_manda_outro_codigo_antes_de_um_minuto(): void
    {
        $this->usuarios->method('emailJaCadastrado')->willReturn(false);
        $this->pendentes->method('buscarPorEmail')->willReturn(
            CadastroPendente::iniciar('Maria', 'maria@email.com', 'h', 'c', 'chave', $this->agora->modify('-30 seconds'))
        );
        $this->enviador->expects($this->never())->method('enviar');

        $this->expectException(ReenvioDeCodigoMuitoCedoException::class);

        $this->useCase()->executar($this->input());
    }

    public function test_nao_guarda_o_cadastro_se_o_email_falhar(): void
    {
        $this->usuarios->method('emailJaCadastrado')->willReturn(false);
        $this->enviador->method('enviar')->willThrowException(EnvioDeEmailFalhouException::porCausaDe(new RuntimeException));
        $this->pendentes->expects($this->never())->method('salvar');

        $this->expectException(EnvioDeEmailFalhouException::class);

        $this->useCase()->executar($this->input());
    }

    private function useCase(): SolicitarCadastroDeClienteUseCase
    {
        $hasher = $this->createStub(PasswordHasherInterface::class);
        $hasher->method('hash')->willReturnCallback(fn (string $valor) => "hash({$valor})");

        $relogio = $this->createStub(RelogioInterface::class);
        $relogio->method('agora')->willReturn($this->agora);

        return new SolicitarCadastroDeClienteUseCase($this->usuarios, $this->pendentes, $this->enviador, $hasher, $relogio);
    }

    private function input(): SolicitarCadastroDeClienteInput
    {
        return new SolicitarCadastroDeClienteInput('Maria Silva', 'maria@email.com', 'senha-segura-123', 'Studio');
    }
}
