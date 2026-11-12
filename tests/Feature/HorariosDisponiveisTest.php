<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\CriaCenarioDeAgenda;
use Tests\TestCase;

class HorariosDisponiveisTest extends TestCase
{
    use RefreshDatabase;
    use CriaCenarioDeAgenda;

    public function test_lista_horarios_livres_excluindo_os_ocupados(): void
    {
        $barbearia = $this->criarBarbearia('dono@barbearia.com');
        ['usuario' => $usuario, 'cliente' => $cliente] = $this->criarCliente('maria@email.com');
        $this->criarAgendamento($barbearia, $cliente, '2030-01-10 10:00:00');

        $response = $this->actingAs($usuario)->getJson('/api/horarios-disponiveis?' . http_build_query([
            'profissional_id' => $barbearia['profissional']->id,
            'duracao_em_minutos' => 30,
            'data' => '2030-01-10',
        ]));

        $this->assertSame(200, $response->getStatusCode(), $response->getContent());
        // Expediente padrão 08:00–19:00 em blocos de 30 min = 22 horários, menos o das 10:00
        $response->assertJsonCount(21);
        $this->assertContains('09:30', $response->json());
        $this->assertNotContains('10:00', $response->json());
    }

    public function test_horarios_ocupados_por_outro_cliente_nao_expoem_dados_dele(): void
    {
        $barbearia = $this->criarBarbearia('dono@barbearia.com');
        ['cliente' => $maria] = $this->criarCliente('maria@email.com', 'Maria Sigilosa');
        ['usuario' => $outroCliente] = $this->criarCliente('joao@email.com', 'João');
        $this->criarAgendamento($barbearia, $maria, '2030-01-10 10:00:00');

        $response = $this->actingAs($outroCliente)->getJson('/api/horarios-disponiveis?' . http_build_query([
            'profissional_id' => $barbearia['profissional']->id,
            'duracao_em_minutos' => 30,
            'data' => '2030-01-10',
        ]));

        $response->assertOk();
        $this->assertNotContains('10:00', $response->json());

        // A resposta é só uma lista de "HH:MM": nada de nome, email ou id de quem agendou
        foreach ($response->json() as $horario) {
            $this->assertMatchesRegularExpression('/^\d{2}:\d{2}$/', $horario);
        }
        $this->assertStringNotContainsString('Maria', $response->getContent());
        $this->assertStringNotContainsString('maria@email.com', $response->getContent());
    }

    public function test_visitante_sem_login_nao_consulta_horarios(): void
    {
        $barbearia = $this->criarBarbearia('dono@barbearia.com');

        $this->getJson('/api/horarios-disponiveis?' . http_build_query([
            'profissional_id' => $barbearia['profissional']->id,
            'duracao_em_minutos' => 30,
            'data' => '2030-01-10',
        ]))->assertUnauthorized();
    }
}
