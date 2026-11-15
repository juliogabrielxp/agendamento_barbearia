<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\CriaCenarioDeAgenda;
use Tests\TestCase;

/**
 * O que o banco recusaria (unique, tamanho do decimal) a validação recusa antes,
 * com 422 e a mensagem no campo, em vez de estourar 500.
 */
class ValidacaoAntesDoBancoTest extends TestCase
{
    use CriaCenarioDeAgenda;
    use RefreshDatabase;

    public function test_perfil_nao_aceita_email_de_outro_estabelecimento(): void
    {
        $minha = $this->criarEstabelecimento('dona@estudio.com');
        $this->criarEstabelecimento('vizinha@estabelecimento.com');

        $this->actingAs($minha['dono'])->putJson('/api/perfil-estabelecimento', [
            'nome' => 'Estúdio',
            'telefone' => '11999999999',
            'email' => 'vizinha@estabelecimento.com',
            'endereco' => 'Rua Teste, 1',
        ])->assertUnprocessable()->assertJsonValidationErrors('email');

        $this->assertDatabaseHas('estabelecimentos', ['id' => $minha['estabelecimento']->id, 'email' => 'dona@estudio.com']);
    }

    public function test_perfil_aceita_manter_o_proprio_email(): void
    {
        $minha = $this->criarEstabelecimento('dona@estudio.com');

        $this->actingAs($minha['dono'])->putJson('/api/perfil-estabelecimento', [
            'nome' => 'Estúdio Novo',
            'telefone' => '11999999999',
            'email' => 'dona@estudio.com',
            'endereco' => 'Rua Teste, 1',
        ])->assertOk();

        $this->assertDatabaseHas('estabelecimentos', ['id' => $minha['estabelecimento']->id, 'nome' => 'Estúdio Novo']);
    }

    public function test_preco_acima_do_que_a_coluna_guarda_da_422(): void
    {
        $estabelecimento = $this->criarEstabelecimento('dona@estudio.com');
        $servico = ['nome' => 'Box braids', 'duracao_em_minutos' => 240, 'preco' => 1000000];

        $this->actingAs($estabelecimento['dono'])->postJson('/api/servicos', $servico)
            ->assertUnprocessable()->assertJsonValidationErrors('preco');

        $this->actingAs($estabelecimento['dono'])->putJson("/api/servicos/{$estabelecimento['servico']->id}", $servico)
            ->assertUnprocessable()->assertJsonValidationErrors('preco');

        $this->actingAs($estabelecimento['dono'])->postJson('/api/servicos', [...$servico, 'preco' => 999999.99])
            ->assertCreated();
    }
}
