<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\CriaCenarioDeAgenda;
use Tests\TestCase;

class StatusDoAgendamentoTest extends TestCase
{
    use RefreshDatabase;
    use CriaCenarioDeAgenda;

    public function test_dono_conclui_agendamento_da_propria_barbearia(): void
    {
        $barbearia = $this->criarBarbearia('dono@barbearia.com');
        ['cliente' => $cliente] = $this->criarCliente('maria@email.com');
        $agendamento = $this->criarAgendamento($barbearia, $cliente, '2030-01-10 10:00:00');

        $response = $this->actingAs($barbearia['dono'])
            ->patchJson("/api/agenda/{$agendamento->id}/status", ['status' => 'concluido']);

        $this->assertSame(200, $response->getStatusCode(), $response->getContent());
        $this->assertDatabaseHas('agendamentos', ['id' => $agendamento->id, 'status' => 'concluido']);
    }

    public function test_dono_nao_altera_agendamento_de_outra_barbearia(): void
    {
        $barbeariaA = $this->criarBarbearia('dono.a@barbearia.com');
        $barbeariaB = $this->criarBarbearia('dono.b@barbearia.com');
        ['cliente' => $cliente] = $this->criarCliente('maria@email.com');
        $agendamentoDaA = $this->criarAgendamento($barbeariaA, $cliente, '2030-01-10 10:00:00');

        $this->actingAs($barbeariaB['dono'])
            ->patchJson("/api/agenda/{$agendamentoDaA->id}/status", ['status' => 'cancelado'])
            ->assertNotFound();

        $this->assertDatabaseHas('agendamentos', ['id' => $agendamentoDaA->id, 'status' => 'confirmado']);
    }

    public function test_cliente_nao_altera_status_nem_do_proprio_agendamento(): void
    {
        $barbearia = $this->criarBarbearia('dono@barbearia.com');
        ['usuario' => $usuario, 'cliente' => $cliente] = $this->criarCliente('maria@email.com');
        $agendamento = $this->criarAgendamento($barbearia, $cliente, '2030-01-10 10:00:00');

        $this->actingAs($usuario)
            ->patchJson("/api/agenda/{$agendamento->id}/status", ['status' => 'concluido'])
            ->assertNotFound();

        $this->assertDatabaseHas('agendamentos', ['id' => $agendamento->id, 'status' => 'confirmado']);
    }
}
