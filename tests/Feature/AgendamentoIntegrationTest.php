<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Infrastructure\Persistence\Eloquent\BarbeariaModel;
use App\Infrastructure\Persistence\Eloquent\ClienteModel;
use App\Infrastructure\Persistence\Eloquent\ProfissionalModel;
use App\Infrastructure\Persistence\Eloquent\ServicoModel;
use App\Infrastructure\Persistence\Eloquent\UserModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AgendamentoIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private UserModel $usuario;

    protected function setUp(): void
    {
        parent::setUp();

        $this->usuario = UserModel::create([
            'nome' => 'Usuário de Teste',
            'email' => 'usuario.teste@email.com',
            'password' => 'senha-segura-123',
        ]);

        $this->actingAs($this->usuario);
    }

    public function test_deve_criar_agendamento_via_http_e_persistir_no_banco(): void
    {

        $barbearia = BarbeariaModel::create([
            'dominio' => 'localhost', // host padrão das requisições de teste
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
        $profissional->servicos()->attach($servico->id);

        $cliente = ClienteModel::create([
            'user_id' => $this->usuario->id,
            'nome' => 'Maria',
            'telefone' => '83988888888',
            'email' => 'maria@teste.com',
        ]);

        $response = $this->postJson('/api/agendamentos', [
            'profissional_id' => $profissional->id,
            'cliente_id' => $cliente->id,
            'servico_id' => $servico->id,
            'duracao_em_minutos' => 30,
            'inicio' => '2030-01-10 10:00:00',
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
            'dominio' => 'localhost', // host padrão das requisições de teste
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
        $profissional->servicos()->attach($servico->id);

        $cliente = ClienteModel::create([
            'user_id' => $this->usuario->id,
            'nome' => 'Maria',
            'telefone' => '83988888888',
            'email' => 'maria@teste.com',
        ]);

        $this->postJson('/api/agendamentos', [
            'profissional_id' => $profissional->id,
            'cliente_id' => $cliente->id,
            'servico_id' => $servico->id,
            'duracao_em_minutos' => 30,
            'inicio' => '2030-01-10 15:00:00',
        ])->assertStatus(201);

        $response = $this->postJson('/api/agendamentos', [
            'profissional_id' => $profissional->id,
            'cliente_id' => $cliente->id,
            'servico_id' => $servico->id,
            'duracao_em_minutos' => 30,
            'inicio' => '2030-01-10 15:15:00',
        ]);

        $response->assertStatus(409);
    }

    public function test_duracao_enviada_pelo_cliente_e_ignorada_e_conflito_retorna_409(): void
    {
        $barbearia = BarbeariaModel::create([
            'dominio' => 'localhost', // host padrão das requisições de teste
            'nome' => 'Barbearia Teste',
            'telefone' => '83999999999',
            'email' => 'teste3@barbearia.com',
            'endereco' => 'Rua Teste, 1',
        ]);

        $servico = ServicoModel::create([
            'barbearia_id' => $barbearia->id,
            'nome' => 'Corte + Barba',
            'duracao_em_minutos' => 60,
            'preco' => 80.0,
        ]);

        $profissional = ProfissionalModel::create([
            'barbearia_id' => $barbearia->id,
            'nome' => 'Carlos',
        ]);
        $profissional->servicos()->attach($servico->id);

        ClienteModel::create([
            'user_id' => $this->usuario->id,
            'nome' => 'Maria',
            'telefone' => '83988888888',
            'email' => 'maria@teste.com',
        ]);

        // Agendamento existente: 10:00 às 11:00 (duração enviada é ignorada pelo servidor)
        $this->postJson('/api/agendamentos', [
            'profissional_id' => $profissional->id,
            'servico_id' => $servico->id,
            'duracao_em_minutos' => 60,
            'inicio' => '2030-01-10 10:00:00',
        ])->assertStatus(201);

        // 09:30 com duração real de 60 min invade o horário das 10:00.
        // Com duracao_em_minutos = 1 vindo do cliente, o conflito passaria despercebido.
        $response = $this->postJson('/api/agendamentos', [
            'profissional_id' => $profissional->id,
            'servico_id' => $servico->id,
            'duracao_em_minutos' => 1,
            'inicio' => '2030-01-10 09:30:00',
        ]);

        $response->assertStatus(409);
        $this->assertDatabaseCount('agendamentos', 1);
    }
}
