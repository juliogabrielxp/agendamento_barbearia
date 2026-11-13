<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Infrastructure\Persistence\Eloquent\ProfissionalModel;
use App\Infrastructure\Persistence\Eloquent\ServicoModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\CriaCenarioDeAgenda;
use Tests\TestCase;

class CadastroDeServicoTest extends TestCase
{
    use CriaCenarioDeAgenda;
    use RefreshDatabase;

    public function test_servico_novo_ja_e_da_profissional_quando_ela_e_a_unica(): void
    {
        $studio = $this->criarBarbearia('dona@studio.com');

        $response = $this->actingAs($studio['dono'])->postJson('/api/servicos', [
            'nome' => 'Box braids',
            'duracao_em_minutos' => 360,
            'preco' => 300,
        ])->assertCreated();

        $this->assertTrue(
            $studio['profissional']->servicos()->whereKey($response->json('id'))->exists(),
            'Sem o vínculo o serviço não aparece na vitrine'
        );
    }

    public function test_com_mais_de_uma_profissional_o_vinculo_continua_manual(): void
    {
        $salao = $this->criarBarbearia('dona@salao.com');
        $segunda = ProfissionalModel::create(['barbearia_id' => $salao['barbearia']->id, 'nome' => 'Ana']);

        $response = $this->actingAs($salao['dono'])->postJson('/api/servicos', [
            'nome' => 'Escova',
            'duracao_em_minutos' => 60,
            'preco' => 80,
        ])->assertCreated();

        $this->assertFalse($salao['profissional']->servicos()->whereKey($response->json('id'))->exists());
        $this->assertFalse($segunda->servicos()->whereKey($response->json('id'))->exists());
    }

    public function test_dona_marca_que_o_servico_pode_atravessar_o_intervalo(): void
    {
        $studio = $this->criarBarbearia('dona@studio.com');

        $this->actingAs($studio['dono'])->putJson("/api/servicos/{$studio['servico']->id}", [
            'nome' => 'Box braids',
            'duracao_em_minutos' => 360,
            'preco' => 300,
            'pode_atravessar_intervalo' => true,
        ])->assertOk()->assertJsonPath('pode_atravessar_intervalo', true);

        $this->assertTrue(ServicoModel::find($studio['servico']->id)->pode_atravessar_intervalo);
    }

    public function test_servico_comeca_sem_poder_atravessar_o_intervalo(): void
    {
        $studio = $this->criarBarbearia('dona@studio.com');

        $this->actingAs($studio['dono'])->postJson('/api/servicos', [
            'nome' => 'Maquiagem',
            'duracao_em_minutos' => 60,
            'preco' => 120,
        ])->assertCreated()->assertJsonPath('pode_atravessar_intervalo', false);
    }
}
