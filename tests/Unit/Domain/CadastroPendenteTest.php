<?php

declare(strict_types=1);

namespace Tests\Unit\Domain;

use App\Domain\CadastroPendente;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

class CadastroPendenteTest extends TestCase
{
    public function test_codigo_tem_seis_digitos(): void
    {
        for ($i = 0; $i < 50; $i++) {
            $this->assertMatchesRegularExpression('/^\d{6}$/', CadastroPendente::gerarCodigo());
        }
    }

    public function test_codigo_vale_por_quinze_minutos(): void
    {
        $cadastro = $this->cadastro(new DateTimeImmutable('2026-11-13 10:00:00'));

        $this->assertFalse($cadastro->expirou(new DateTimeImmutable('2026-11-13 10:14:59')));
        $this->assertTrue($cadastro->expirou(new DateTimeImmutable('2026-11-13 10:15:00')));
    }

    public function test_so_reconhece_a_chave_de_quem_iniciou_o_cadastro(): void
    {
        $cadastro = $this->cadastro(new DateTimeImmutable, 'chave-certa');

        $this->assertTrue($cadastro->pertenceA('chave-certa'));
        $this->assertFalse($cadastro->pertenceA('outra-chave'));
    }

    public function test_tentativas_esgotam_no_limite(): void
    {
        $agora = new DateTimeImmutable;
        $base = $this->cadastro($agora);

        $quaseNoLimite = new CadastroPendente('Maria', 'maria@email.com', 'h', 'c', $base->chaveHash, 4, $base->expiraEm, $agora);
        $noLimite = new CadastroPendente('Maria', 'maria@email.com', 'h', 'c', $base->chaveHash, 5, $base->expiraEm, $agora);

        $this->assertFalse($quaseNoLimite->tentativasEsgotadas());
        $this->assertTrue($noLimite->tentativasEsgotadas());
    }

    public function test_reenvio_so_depois_de_um_minuto(): void
    {
        $cadastro = $this->cadastro(new DateTimeImmutable('2026-11-13 10:00:00'));

        $this->assertSame(40, $cadastro->segundosParaPoderReenviar(new DateTimeImmutable('2026-11-13 10:00:20')));
        $this->assertSame(0, $cadastro->segundosParaPoderReenviar(new DateTimeImmutable('2026-11-13 10:01:00')));
    }

    public function test_novo_codigo_zera_tentativas_e_renova_validade_mantendo_senha_e_chave(): void
    {
        $inicio = new DateTimeImmutable('2026-11-13 10:00:00');
        $cadastro = new CadastroPendente('Maria', 'maria@email.com', 'hash-senha', 'codigo-velho', 'hash-chave', 3, $inicio, $inicio);

        $novo = $cadastro->comNovoCodigo('codigo-novo', new DateTimeImmutable('2026-11-13 10:05:00'));

        $this->assertSame(0, $novo->tentativas);
        $this->assertSame('codigo-novo', $novo->codigoHash);
        $this->assertSame('hash-senha', $novo->senhaHash);
        $this->assertSame('hash-chave', $novo->chaveHash);
        $this->assertEquals(new DateTimeImmutable('2026-11-13 10:20:00'), $novo->expiraEm);
    }

    private function cadastro(DateTimeImmutable $agora, string $chave = 'chave'): CadastroPendente
    {
        return CadastroPendente::iniciar('Maria', 'maria@email.com', 'hash-senha', 'hash-codigo', $chave, $agora);
    }
}
