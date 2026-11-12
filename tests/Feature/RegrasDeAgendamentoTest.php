<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domain\Contracts\NotificadorDePushInterface;
use App\Domain\Exceptions\AgendamentoInvalidoException;
use App\Infrastructure\Persistence\Eloquent\ServicoModel;
use App\Infrastructure\Persistence\Eloquent\UserModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\Feature\Concerns\CriaCenarioDeAgenda;
use Tests\TestCase;

/**
 * Regras que antes só existiam no front. Expediente padrão da barbearia: 08:00–19:00,
 * serviço de 30 min. "Agora" é fixado em 09/01/2030 12:00.
 */
class RegrasDeAgendamentoTest extends TestCase
{
    use RefreshDatabase;
    use CriaCenarioDeAgenda;

    private array $barbearia;
    private UserModel $usuarioCliente;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo('2030-01-09 12:00:00');
        $this->mock(NotificadorDePushInterface::class)->shouldIgnoreMissing();

        $this->barbearia = $this->criarBarbearia('dono@barbearia.com');
        ['usuario' => $this->usuarioCliente] = $this->criarCliente('maria@email.com');
    }

    public function test_rejeita_horario_no_passado(): void
    {
        // Mesmo dia, mas às 10:00, e agora são 12:00
        $this->assertInvalido(
            $this->agendar('2030-01-09 10:00:00'),
            AgendamentoInvalidoException::noPassado()
        );
    }

    public function test_rejeita_horario_antes_da_abertura(): void
    {
        $this->assertInvalido(
            $this->agendar('2030-01-10 07:30:00'),
            AgendamentoInvalidoException::foraDoExpediente()
        );
    }

    public function test_rejeita_horario_que_termina_depois_do_fechamento(): void
    {
        // 18:45 + 30 min = 19:15, depois do fechamento
        $this->assertInvalido(
            $this->agendar('2030-01-10 18:45:00'),
            AgendamentoInvalidoException::foraDoExpediente()
        );

        // 18:30 + 30 min termina exatamente às 19:00: permitido
        $this->agendar('2030-01-10 18:30:00')->assertCreated();
    }

    public function test_rejeita_horario_que_cruza_o_intervalo_de_almoco(): void
    {
        $this->barbearia['barbearia']->update(['intervalo_inicio' => '12:00', 'intervalo_fim' => '13:00']);

        // 11:45–12:15 invade o começo do intervalo; 12:30 cai dentro dele
        foreach (['2030-01-10 11:45:00', '2030-01-10 12:30:00'] as $inicio) {
            $this->assertInvalido($this->agendar($inicio), AgendamentoInvalidoException::noIntervalo());
        }

        // Encostar no intervalo, sem cruzar, é permitido
        $this->agendar('2030-01-10 11:30:00')->assertCreated();
        $this->agendar('2030-01-10 13:00:00')->assertCreated();
    }

    public function test_rejeita_servico_que_o_profissional_nao_realiza(): void
    {
        $barba = ServicoModel::create([
            'barbearia_id' => $this->barbearia['barbearia']->id,
            'nome' => 'Barba',
            'duracao_em_minutos' => 30,
            'preco' => 30.0,
        ]);

        $this->assertInvalido(
            $this->agendar('2030-01-10 10:00:00', servicoId: $barba->id),
            AgendamentoInvalidoException::servicoNaoRealizadoPeloProfissional()
        );
    }

    public function test_rejeita_servico_de_outra_barbearia(): void
    {
        $outra = $this->criarBarbearia('outra@barbearia.com');

        $this->assertInvalido(
            $this->agendar('2030-01-10 10:00:00', servicoId: $outra['servico']->id),
            AgendamentoInvalidoException::servicoNaoRealizadoPeloProfissional()
        );
    }

    public function test_rejeita_profissional_inexistente_com_422_e_nao_500(): void
    {
        $this->assertInvalido(
            $this->agendar('2030-01-10 10:00:00', profissionalId: 999999),
            AgendamentoInvalidoException::profissionalInexistente()
        );
    }

    public function test_agendamento_cancelado_libera_o_horario(): void
    {
        ['cliente' => $outroCliente] = $this->criarCliente('joao@email.com');
        $cancelado = $this->criarAgendamento($this->barbearia, $outroCliente, '2030-01-10 10:00:00');
        $cancelado->update(['status' => 'cancelado']);

        $this->agendar('2030-01-10 10:00:00')->assertCreated();
        $this->assertDatabaseCount('agendamentos', 2);
    }

    public function test_agendamento_confirmado_continua_ocupando_o_horario(): void
    {
        ['cliente' => $outroCliente] = $this->criarCliente('joao@email.com');
        $this->criarAgendamento($this->barbearia, $outroCliente, '2030-01-10 10:00:00');

        $this->agendar('2030-01-10 10:00:00')->assertStatus(409);
    }

    private function agendar(string $inicio, ?int $servicoId = null, ?int $profissionalId = null): TestResponse
    {
        return $this->actingAs($this->usuarioCliente)->postJson('/api/agendamentos', [
            'profissional_id' => $profissionalId ?? $this->barbearia['profissional']->id,
            'servico_id' => $servicoId ?? $this->barbearia['servico']->id,
            'inicio' => $inicio,
        ]);
    }

    private function assertInvalido(TestResponse $response, AgendamentoInvalidoException $esperada): void
    {
        $this->assertSame(422, $response->getStatusCode(), $response->getContent());
        $response->assertJsonPath('erro', $esperada->getMessage());
        $this->assertDatabaseMissing('agendamentos', ['inicio' => $response->baseRequest->input('inicio')]);
    }
}
