<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domain\Contracts\NotificadorDePushInterface;
use App\Infrastructure\Persistence\Eloquent\AgendamentoModel;
use App\Infrastructure\Persistence\Eloquent\BarbeariaModel;
use App\Infrastructure\Persistence\Eloquent\ClienteModel;
use App\Infrastructure\Persistence\Eloquent\ProfissionalModel;
use App\Infrastructure\Persistence\Eloquent\ServicoModel;
use App\Infrastructure\Persistence\Eloquent\UserModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Mockery\MockInterface;
use Tests\TestCase;

class NotificacaoDeAgendamentoTest extends TestCase
{
    use RefreshDatabase;

    private UserModel $dono;
    private ProfissionalModel $profissional;
    private ServicoModel $servico;
    private ClienteModel $cliente;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dono = UserModel::create(['nome' => 'Dono', 'email' => 'dono@barbearia.com', 'password' => 'senha-segura-123']);

        $barbearia = BarbeariaModel::create([
            'user_id' => $this->dono->id,
            'nome' => 'Barbearia Teste',
            'email' => 'dono@barbearia.com',
            'telefone' => '',
            'endereco' => 'Rua Teste, 1',
        ]);

        $this->servico = ServicoModel::create([
            'barbearia_id' => $barbearia->id,
            'nome' => 'Corte',
            'duracao_em_minutos' => 30,
            'preco' => 40.0,
        ]);

        $this->profissional = ProfissionalModel::create(['barbearia_id' => $barbearia->id, 'nome' => 'João']);

        $usuarioCliente = UserModel::create(['nome' => 'Maria', 'email' => 'maria@email.com', 'password' => 'senha-segura-123']);

        $this->cliente = ClienteModel::create([
            'user_id' => $usuarioCliente->id,
            'nome' => 'Maria',
            'email' => 'maria@email.com',
            'telefone' => '',
        ]);

        $this->actingAs($usuarioCliente);
    }

    public function test_dono_e_notificado_quando_cliente_agenda(): void
    {
        $donoId = $this->dono->id;

        $this->mock(NotificadorDePushInterface::class, function (MockInterface $mock) use ($donoId) {
            $mock->shouldReceive('enviarParaUsuario')
                ->once()
                ->withArgs(fn (int $userId, string $titulo, string $mensagem) =>
                    $userId === $donoId && str_contains($mensagem, 'Maria'))
                ->andReturn(1);
        });

        $response = $this->agendar();

        $this->assertSame(201, $response->getStatusCode(), $response->getContent());
    }

    public function test_dono_nao_e_notificado_quando_o_horario_conflita(): void
    {
        AgendamentoModel::create([
            'profissional_id' => $this->profissional->id,
            'cliente_id' => $this->cliente->id,
            'servico_id' => $this->servico->id,
            'inicio' => '2026-09-01 10:00:00',
        ]);

        $this->mock(NotificadorDePushInterface::class, function (MockInterface $mock) {
            $mock->shouldNotReceive('enviarParaUsuario');
        });

        $response = $this->agendar();

        $this->assertSame(409, $response->getStatusCode(), $response->getContent());
    }

    private function agendar(): TestResponse
    {
        return $this->postJson('/api/agendamentos', [
            'profissional_id' => $this->profissional->id,
            'servico_id' => $this->servico->id,
            'duracao_em_minutos' => 30,
            'inicio' => '2026-09-01 10:00:00',
        ]);
    }
}
