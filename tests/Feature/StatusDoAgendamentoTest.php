<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\CriaCenarioDeAgenda;
use Tests\TestCase;

class StatusDoAgendamentoTest extends TestCase
{
    use CriaCenarioDeAgenda;
    use RefreshDatabase;

    public function test_dono_conclui_agendamento_da_propria_estabelecimento(): void
    {
        $estabelecimento = $this->criarEstabelecimento('dono@estabelecimento.com');
        ['cliente' => $cliente] = $this->criarCliente('maria@email.com');
        $agendamento = $this->criarAgendamento($estabelecimento, $cliente, '2030-01-10 10:00:00');

        $response = $this->actingAs($estabelecimento['dono'])
            ->patchJson("/api/agenda/{$agendamento->id}/status", ['status' => 'concluido']);

        $this->assertSame(200, $response->getStatusCode(), $response->getContent());
        $this->assertDatabaseHas('agendamentos', ['id' => $agendamento->id, 'status' => 'concluido']);
    }

    public function test_dono_nao_altera_agendamento_de_outra_estabelecimento(): void
    {
        $estabelecimentoA = $this->criarEstabelecimento('dono.a@estabelecimento.com');
        $estabelecimentoB = $this->criarEstabelecimento('dono.b@estabelecimento.com');
        ['cliente' => $cliente] = $this->criarCliente('maria@email.com');
        $agendamentoDaA = $this->criarAgendamento($estabelecimentoA, $cliente, '2030-01-10 10:00:00');

        $this->actingAs($estabelecimentoB['dono'])
            ->patchJson("/api/agenda/{$agendamentoDaA->id}/status", ['status' => 'cancelado'])
            ->assertNotFound();

        $this->assertDatabaseHas('agendamentos', ['id' => $agendamentoDaA->id, 'status' => 'confirmado']);
    }

    public function test_cliente_nao_altera_status_nem_do_proprio_agendamento(): void
    {
        $estabelecimento = $this->criarEstabelecimento('dono@estabelecimento.com');
        ['usuario' => $usuario, 'cliente' => $cliente] = $this->criarCliente('maria@email.com');
        $agendamento = $this->criarAgendamento($estabelecimento, $cliente, '2030-01-10 10:00:00');

        $this->actingAs($usuario)
            ->patchJson("/api/agenda/{$agendamento->id}/status", ['status' => 'concluido'])
            ->assertNotFound();

        $this->assertDatabaseHas('agendamentos', ['id' => $agendamento->id, 'status' => 'confirmado']);
    }
}
