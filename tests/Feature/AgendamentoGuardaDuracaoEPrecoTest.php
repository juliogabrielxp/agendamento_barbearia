<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domain\Contracts\NotificadorDePushInterface;
use App\Infrastructure\Persistence\Eloquent\AgendamentoModel;
use App\Infrastructure\Persistence\Eloquent\ServicoModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\CriaCenarioDeAgenda;
use Tests\TestCase;

/**
 * O agendamento guarda a duração e o preço do serviço no momento da reserva. Se a dona
 * muda o serviço depois, quem já reservou continua ocupando o tempo que reservou e
 * pagando o preço que viu; só as reservas novas usam o cadastro novo.
 */
class AgendamentoGuardaDuracaoEPrecoTest extends TestCase
{
    use CriaCenarioDeAgenda;
    use RefreshDatabase;

    private array $estabelecimento;

    private ServicoModel $tranca;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo('2030-01-09 12:00:00');
        $this->mock(NotificadorDePushInterface::class)->shouldIgnoreMissing();

        // Expediente padrão 08:00–19:00; o serviço "Corte" do cenário tem 30 min
        $this->estabelecimento = $this->criarBarbearia('dona@estudio.com');
        $this->tranca = ServicoModel::create([
            'barbearia_id' => $this->estabelecimento['barbearia']->id,
            'nome' => 'Box braids',
            'duracao_em_minutos' => 240,
            'preco' => 250.0,
        ]);
        $this->estabelecimento['profissional']->servicos()->attach($this->tranca->id);

        ['usuario' => $maria] = $this->criarCliente('maria@email.com');
        $this->actingAs($maria)->postJson('/api/agendamentos', [
            'profissional_id' => $this->estabelecimento['profissional']->id,
            'servico_id' => $this->tranca->id,
            'inicio' => '2030-01-10 10:00:00',
        ])->assertCreated();
    }

    public function test_encurtar_o_servico_nao_libera_o_tempo_de_quem_ja_reservou(): void
    {
        $this->donaMudaATranca(duracao: 120, preco: 300.0);

        // Maria reservou 10:00–14:00: nada entre 10:00 e 13:30 pode aparecer livre
        $livres = $this->getJson('/api/horarios-disponiveis?'.http_build_query([
            'profissional_id' => $this->estabelecimento['profissional']->id,
            'servico_id' => $this->estabelecimento['servico']->id,
            'data' => '2030-01-10',
        ]))->assertOk()->json();

        $this->assertNotContains('12:00', $livres);
        $this->assertNotContains('13:30', $livres);
        $this->assertContains('14:00', $livres);

        ['usuario' => $ana] = $this->criarCliente('ana@email.com');
        $this->actingAs($ana)->postJson('/api/agendamentos', [
            'profissional_id' => $this->estabelecimento['profissional']->id,
            'servico_id' => $this->estabelecimento['servico']->id,
            'inicio' => '2030-01-10 12:00:00',
        ])->assertStatus(409);
    }

    public function test_reserva_guarda_a_duracao_e_o_preco_do_momento(): void
    {
        $this->donaMudaATranca(duracao: 120, preco: 300.0);

        $agendamento = AgendamentoModel::where('servico_id', $this->tranca->id)->sole();
        $this->assertSame(240, (int) $agendamento->duracao_em_minutos);
        $this->assertSame('250.00', (string) $agendamento->preco);
    }

    private function donaMudaATranca(int $duracao, float $preco): void
    {
        $this->actingAs($this->estabelecimento['dono'])->putJson("/api/servicos/{$this->tranca->id}", [
            'nome' => 'Box braids',
            'duracao_em_minutos' => $duracao,
            'preco' => $preco,
        ])->assertOk();
    }
}
