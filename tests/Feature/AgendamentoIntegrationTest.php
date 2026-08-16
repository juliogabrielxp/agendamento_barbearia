<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Infrastructure\Persistence\Eloquent\BarbeariaModel;
use App\Infrastructure\Persistence\Eloquent\ClienteModel;
use App\Infrastructure\Persistence\Eloquent\ProfissionalModel;
use App\Infrastructure\Persistence\Eloquent\ServicoModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AgendamentoIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_deve_criar_agendamento_via_http_e_persistir_no_banco(): void
    {

        $barbearia = BarbeariaModel::create([
            'nome' => 'Barbearia Teste',
            'telefone' => '83999999999',
            'email' => 'teste@barbearia.com',
            'endereco' => 'Rua Teste, 1',
        ]);

        $servico = ServicoModel::create([
            'barbearia_id' => $barbearia->id,
            'nome' => 'Corte',
            'duracao_em_minutos' => 30,
            'preco' => 50.0,
        ]);

        $profissional = ProfissionalModel::create([
            'barbearia_id' => $barbearia->id,
            'nome' => 'João',
        ]);

        $cliente = ClienteModel::create([
            'nome' => 'Maria',
            'telefone' => '83988888888',
            'email' => 'maria@teste.com',
        ]);


        $response = $this->postJson('/api/agendamentos', [
            'profissional_id' => $profissional->id,
            'cliente_id' => $cliente->id,
            'servico_id' => $servico->id,
            'duracao_em_minutos' => 30,
            'inicio' => '2026-09-01 10:00:00',
        ]);


        $response->assertStatus(201);

        $this->assertDatabaseHas('agendamentos', [
            'profissional_id' => $profissional->id,
            'cliente_id' => $cliente->id,
        ]);
    }

    public function test_deve_retornar_409_ao_tentar_agendar_horario_conflitante(): void
    {

        $barbearia = BarbeariaModel::create([
            'nome' => 'Barbearia Teste',
            'telefone' => '83999999999',
            'email' => 'teste2@barbearia.com',
            'endereco' => 'Rua Teste, 1',
        ]);

        $servico = ServicoModel::create([
            'barbearia_id' => $barbearia->id,
            'nome' => 'Corte',
            'duracao_em_minutos' => 30,
            'preco' => 50.0,
        ]);

        $profissional = ProfissionalModel::create([
            'barbearia_id' => $barbearia->id,
            'nome' => 'Pedro',
        ]);

        $cliente = ClienteModel::create([
            'nome' => 'Ana',
            'telefone' => '83977777777',
            'email' => 'ana@teste.com',
        ]);


        $this->postJson('/api/agendamentos', [
            'profissional_id' => $profissional->id,
            'cliente_id' => $cliente->id,
            'servico_id' => $servico->id,
            'duracao_em_minutos' => 30,
            'inicio' => '2026-09-01 15:00:00',
        ])->assertStatus(201);


        $response = $this->postJson('/api/agendamentos', [
            'profissional_id' => $profissional->id,
            'cliente_id' => $cliente->id,
            'servico_id' => $servico->id,
            'duracao_em_minutos' => 30,
            'inicio' => '2026-09-01 15:15:00',
        ]);

        
        $response->assertStatus(409);
    }
}
