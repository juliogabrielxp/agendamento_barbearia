<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\CriaCenarioDeAgenda;
use Tests\TestCase;

/**
 * O painel da dona só abre no endereço do estabelecimento dela. O login por senha
 * já exige isso; aqui o teste cobre qualquer sessão (inclusive a que nasce no login
 * com Google, que não passa por essa conferência).
 */
class PainelNoDominioDoEstabelecimentoTest extends TestCase
{
    use CriaCenarioDeAgenda;
    use RefreshDatabase;

    public function test_dona_usa_o_painel_no_proprio_dominio(): void
    {
        $minha = $this->criarBarbearia('dona@estudio.com', 'estudio.test');

        $this->actingAs($minha['dono'])->noHost('estudio.test')
            ->getJson('/api/servicos')
            ->assertOk()
            ->assertJsonPath('0.id', $minha['servico']->id);
    }

    public function test_dona_nao_usa_o_painel_no_dominio_de_outro_estabelecimento(): void
    {
        $this->criarBarbearia('vizinha@barbearia.com', 'vizinha.test');
        $minha = $this->criarBarbearia('dona@estudio.com', 'estudio.test');

        $this->actingAs($minha['dono'])->noHost('vizinha.test');

        $this->getJson('/api/servicos')->assertNotFound();
        $this->getJson('/api/profissionais')->assertNotFound();
        $this->getJson('/api/agenda')->assertNotFound();
        $this->getJson('/api/configuracao-estabelecimento')->assertNotFound();
        $this->getJson('/api/perfil-estabelecimento')->assertNotFound();

        $this->putJson('/api/perfil-estabelecimento', [
            'nome' => 'Alterado de fora',
            'telefone' => '11999999999',
            'email' => 'dona@estudio.com',
            'endereco' => 'Rua Teste, 1',
        ])->assertNotFound();

        $this->assertDatabaseHas('barbearias', ['id' => $minha['barbearia']->id, 'nome' => $minha['barbearia']->nome]);
    }

    public function test_me_so_apresenta_a_dona_no_dominio_dela(): void
    {
        $this->criarBarbearia('vizinha@barbearia.com', 'vizinha.test');
        $minha = $this->criarBarbearia('dona@estudio.com', 'estudio.test');

        $this->actingAs($minha['dono'])->noHost('estudio.test')
            ->getJson('/api/me')
            ->assertOk()
            ->assertJsonPath('estabelecimento.id', $minha['barbearia']->id);

        $this->noHost('vizinha.test')
            ->getJson('/api/me')
            ->assertOk()
            ->assertJsonPath('estabelecimento', null);
    }
}
