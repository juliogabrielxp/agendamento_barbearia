<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domain\Contracts\NotificadorDePushInterface;
use App\Infrastructure\Persistence\Eloquent\ServicoModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\CriaCenarioDeAgenda;
use Tests\TestCase;

/**
 * Cada estabelecimento responde no próprio host. A vitrine é pública e nada
 * de um estabelecimento vaza para o endereço de outro.
 */
class EstabelecimentoPorDominioTest extends TestCase
{
    use CriaCenarioDeAgenda;
    use RefreshDatabase;

    public function test_visitante_ve_a_vitrine_sem_login(): void
    {
        $studio = $this->criarBarbearia('dona@studio.com', 'trancas.test');
        $studio['barbearia']->update(['nome' => 'Studio Girassol Beauty', 'telefone' => '(83) 99999-0000']);

        $response = $this->noHost('trancas.test')->getJson('/api/estabelecimento');

        $response->assertOk()
            ->assertJsonPath('nome', 'Studio Girassol Beauty')
            ->assertJsonPath('telefone', '(83) 99999-0000')
            ->assertJsonPath('hora_abertura', '08:00')
            ->assertJsonPath('servicos.0.nome', 'Corte')
            ->assertJsonPath('servicos.0.duracao_em_minutos', 30)
            ->assertJsonPath('profissionais.0.servicos', [$studio['servico']->id]);
    }

    public function test_vitrine_publica_nao_expoe_dados_da_conta(): void
    {
        $studio = $this->criarBarbearia('dona@studio.com', 'trancas.test');
        $studio['barbearia']->update(['nome' => 'Studio Girassol Beauty']);

        $response = $this->noHost('trancas.test')->getJson('/api/estabelecimento')->assertOk();

        $this->assertSame(
            ['nome', 'endereco', 'telefone', 'imagem_url', 'hora_abertura', 'hora_fechamento', 'servicos', 'profissionais'],
            array_keys($response->json())
        );
        $this->assertStringNotContainsString('dona@studio.com', $response->getContent());
        $this->assertStringNotContainsString('user_id', $response->getContent());
        $this->assertStringNotContainsString('pivot', $response->getContent());
    }

    public function test_vitrine_mostra_so_os_servicos_do_proprio_estabelecimento(): void
    {
        $this->criarBarbearia('dona@studio.com', 'trancas.test');
        $salao = $this->criarBarbearia('dona@salao.com', 'salao.test');
        $salao['servico']->update(['nome' => 'Escova do salão']);

        $response = $this->noHost('trancas.test')->getJson('/api/estabelecimento');

        $this->assertStringNotContainsString('Escova do salão', $response->getContent());
    }

    public function test_vitrine_esconde_servico_que_ninguem_realiza(): void
    {
        $studio = $this->criarBarbearia('dona@studio.com', 'trancas.test');
        ServicoModel::create([
            'barbearia_id' => $studio['barbearia']->id,
            'nome' => 'Rascunho sem profissional',
            'duracao_em_minutos' => 30,
            'preco' => 10.0,
        ]);

        $response = $this->noHost('trancas.test')->getJson('/api/estabelecimento');

        $response->assertOk()->assertJsonCount(1, 'servicos');
    }

    public function test_host_sem_estabelecimento_responde_404(): void
    {
        $this->criarBarbearia('dona@studio.com', 'trancas.test');

        $this->withoutVite();

        $this->noHost('barbearia.test')->getJson('/api/estabelecimento')->assertNotFound();
        $this->noHost('barbearia.test')->get('/app')->assertNotFound();
    }

    public function test_pagina_e_manifest_levam_o_nome_do_estabelecimento(): void
    {
        $studio = $this->criarBarbearia('dona@studio.com', 'trancas.test');
        $studio['barbearia']->update(['nome' => 'Studio Girassol Beauty']);
        $this->withoutVite();

        $this->noHost('trancas.test')->get('/app/qualquer/rota')
            ->assertOk()
            ->assertSee('<title>Studio Girassol Beauty</title>', escape: false);

        $this->noHost('trancas.test')->get('/manifest.webmanifest')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/manifest+json')
            ->assertJsonPath('name', 'Studio Girassol Beauty');
    }

    public function test_cliente_ve_so_os_agendamentos_do_estabelecimento_do_endereco(): void
    {
        $studio = $this->criarBarbearia('dona@studio.com', 'trancas.test');
        $salao = $this->criarBarbearia('dona@salao.com', 'salao.test');
        ['usuario' => $usuario, 'cliente' => $cliente] = $this->criarCliente('maria@email.com');
        $doStudio = $this->criarAgendamento($studio, $cliente, '2030-01-10 10:00:00');
        $this->criarAgendamento($salao, $cliente, '2030-01-11 10:00:00');

        $response = $this->actingAs($usuario)->noHost('trancas.test')->getJson('/api/meus-agendamentos');

        $response->assertOk()->assertJsonCount(1)->assertJsonPath('0.id', $doStudio->id);
    }

    public function test_nao_agenda_servico_de_um_estabelecimento_pelo_endereco_de_outro(): void
    {
        $this->travelTo('2030-01-09 12:00:00');
        $this->mock(NotificadorDePushInterface::class)->shouldIgnoreMissing();
        $studio = $this->criarBarbearia('dona@studio.com', 'trancas.test');
        $this->criarBarbearia('dona@salao.com', 'salao.test');
        ['usuario' => $usuario] = $this->criarCliente('maria@email.com');

        $this->actingAs($usuario)->noHost('salao.test')->postJson('/api/agendamentos', [
            'profissional_id' => $studio['profissional']->id,
            'servico_id' => $studio['servico']->id,
            'inicio' => '2030-01-10 10:00:00',
        ])->assertStatus(422);

        $this->assertDatabaseCount('agendamentos', 0);
    }
}
