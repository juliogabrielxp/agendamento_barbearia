<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Infrastructure\Persistence\Eloquent\ServicoModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\Feature\Concerns\CriaCenarioDeAgenda;
use Tests\TestCase;

/**
 * Expediente padrão 08:00–19:00. "Agora" é fixado em 09/01/2030 12:00.
 */
class HorariosDisponiveisTest extends TestCase
{
    use CriaCenarioDeAgenda;
    use RefreshDatabase;

    private array $estabelecimento;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo('2030-01-09 12:00:00');
        $this->estabelecimento = $this->criarBarbearia('dona@studio.com');
    }

    public function test_visitante_sem_login_consulta_horarios(): void
    {
        $response = $this->consultar('2030-01-10');

        $this->assertSame(200, $response->getStatusCode(), $response->getContent());
        // 08:00–19:00 em grade de 30 min para um serviço de 30 min = 22 horários
        $response->assertJsonCount(22);
    }

    public function test_lista_horarios_livres_excluindo_os_ocupados(): void
    {
        ['cliente' => $cliente] = $this->criarCliente('maria@email.com');
        $this->criarAgendamento($this->estabelecimento, $cliente, '2030-01-10 10:00:00');

        $response = $this->consultar('2030-01-10');

        $response->assertOk()->assertJsonCount(21);
        $this->assertContains('09:30', $response->json());
        $this->assertNotContains('10:00', $response->json());
    }

    public function test_horarios_ocupados_por_outro_cliente_nao_expoem_dados_dele(): void
    {
        ['cliente' => $maria] = $this->criarCliente('maria@email.com', 'Maria Sigilosa');
        $this->criarAgendamento($this->estabelecimento, $maria, '2030-01-10 10:00:00');

        $response = $this->consultar('2030-01-10');

        $response->assertOk();
        $this->assertNotContains('10:00', $response->json());

        // A resposta é só uma lista de "HH:MM": nada de nome, email ou id de quem agendou
        foreach ($response->json() as $horario) {
            $this->assertMatchesRegularExpression('/^\d{2}:\d{2}$/', $horario);
        }
        $this->assertStringNotContainsString('Maria', $response->getContent());
        $this->assertStringNotContainsString('maria@email.com', $response->getContent());
    }

    public function test_nao_oferece_horario_que_ja_passou(): void
    {
        $horarios = $this->consultar('2030-01-09')->assertOk()->json();

        $this->assertNotContains('11:30', $horarios);
        $this->assertNotContains('12:00', $horarios);
        $this->assertSame('12:30', $horarios[0]);
    }

    public function test_servico_longo_anda_na_grade_de_30_minutos(): void
    {
        $trancas = $this->criarServico('Box braids', duracao: 360);

        $horarios = $this->consultar('2030-01-10', $trancas)->assertOk()->json();

        // Antes a grade andava pela duração e só sobrava 08:00
        $this->assertSame(['08:00', '08:30', '09:00', '09:30', '10:00', '10:30', '11:00', '11:30', '12:00', '12:30', '13:00'], $horarios);
    }

    public function test_servico_que_pode_atravessar_o_intervalo_so_nao_comeca_nele(): void
    {
        $this->estabelecimento['barbearia']->update(['intervalo_inicio' => '12:00', 'intervalo_fim' => '13:00']);
        $trancas = $this->criarServico('Box braids', duracao: 240, podeAtravessarIntervalo: true);

        $horarios = $this->consultar('2030-01-10', $trancas)->assertOk()->json();

        $this->assertContains('09:00', $horarios); // 09:00–13:00 passa por cima do almoço
        $this->assertNotContains('12:00', $horarios);
        $this->assertNotContains('12:30', $horarios);
        $this->assertContains('13:00', $horarios);
    }

    public function test_servico_comum_continua_bloqueado_pelo_intervalo(): void
    {
        $this->estabelecimento['barbearia']->update(['intervalo_inicio' => '12:00', 'intervalo_fim' => '13:00']);
        $escova = $this->criarServico('Escova', duracao: 60);

        $horarios = $this->consultar('2030-01-10', $escova)->assertOk()->json();

        $this->assertContains('11:00', $horarios);
        $this->assertNotContains('11:30', $horarios);
        $this->assertNotContains('12:00', $horarios);
        $this->assertContains('13:00', $horarios);
    }

    public function test_duracao_vem_do_cadastro_e_nao_do_request(): void
    {
        $response = $this->getJson('/api/horarios-disponiveis?'.http_build_query([
            'profissional_id' => $this->estabelecimento['profissional']->id,
            'servico_id' => $this->estabelecimento['servico']->id,
            'duracao_em_minutos' => 600,
            'data' => '2030-01-10',
        ]));

        $response->assertOk()->assertJsonCount(22);
    }

    public function test_nao_consulta_servico_de_outro_estabelecimento(): void
    {
        $outro = $this->criarBarbearia('dono@outro.com');

        $this->consultar('2030-01-10', $outro['servico'])->assertNotFound();
    }

    public function test_host_sem_estabelecimento_responde_404(): void
    {
        $this->noHost('desconhecido.test')->consultar('2030-01-10')->assertNotFound();
    }

    private function consultar(string $data, ?ServicoModel $servico = null): TestResponse
    {
        return $this->getJson('/api/horarios-disponiveis?'.http_build_query([
            'profissional_id' => $this->estabelecimento['profissional']->id,
            'servico_id' => ($servico ?? $this->estabelecimento['servico'])->id,
            'data' => $data,
        ]));
    }

    private function criarServico(string $nome, int $duracao, bool $podeAtravessarIntervalo = false): ServicoModel
    {
        $servico = ServicoModel::create([
            'barbearia_id' => $this->estabelecimento['barbearia']->id,
            'nome' => $nome,
            'duracao_em_minutos' => $duracao,
            'preco' => 300.0,
            'pode_atravessar_intervalo' => $podeAtravessarIntervalo,
        ]);
        $this->estabelecimento['profissional']->servicos()->attach($servico->id);

        return $servico;
    }
}
