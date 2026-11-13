<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\CriaCenarioDeAgenda;
use Tests\TestCase;

class CancelamentoPeloClienteTest extends TestCase
{
    use CriaCenarioDeAgenda;
    use RefreshDatabase;

    public function test_cliente_cancela_o_proprio_agendamento(): void
    {
        $barbearia = $this->criarBarbearia('dono@barbearia.com');
        ['usuario' => $usuario, 'cliente' => $cliente] = $this->criarCliente('maria@email.com');
        $agendamento = $this->criarAgendamento($barbearia, $cliente, '2030-01-10 10:00:00');

        $this->actingAs($usuario)
            ->deleteJson("/api/meus-agendamentos/{$agendamento->id}")
            ->assertNoContent();

        $this->assertDatabaseMissing('agendamentos', ['id' => $agendamento->id]);
    }

    public function test_cliente_nao_cancela_agendamento_de_outro_cliente(): void
    {
        $barbearia = $this->criarBarbearia('dono@barbearia.com');
        ['cliente' => $maria] = $this->criarCliente('maria@email.com', 'Maria');
        ['usuario' => $invasor] = $this->criarCliente('invasor@email.com', 'Invasor');
        $agendamentoDaMaria = $this->criarAgendamento($barbearia, $maria, '2030-01-10 10:00:00');

        // 404 e não 204: responder sucesso sem ter apagado nada engana o front
        $this->actingAs($invasor)
            ->deleteJson("/api/meus-agendamentos/{$agendamentoDaMaria->id}")
            ->assertNotFound();

        $this->assertDatabaseHas('agendamentos', ['id' => $agendamentoDaMaria->id, 'cliente_id' => $maria->id]);
    }

    public function test_dono_de_barbearia_nao_cancela_pela_rota_do_cliente(): void
    {
        $barbearia = $this->criarBarbearia('dono@barbearia.com');
        ['cliente' => $cliente] = $this->criarCliente('maria@email.com');
        $agendamento = $this->criarAgendamento($barbearia, $cliente, '2030-01-10 10:00:00');

        $this->actingAs($barbearia['dono'])
            ->deleteJson("/api/meus-agendamentos/{$agendamento->id}")
            ->assertNotFound();

        $this->assertDatabaseHas('agendamentos', ['id' => $agendamento->id]);
    }

    public function test_cancelar_agendamento_inexistente_responde_404(): void
    {
        ['usuario' => $usuario] = $this->criarCliente('maria@email.com');

        $this->actingAs($usuario)
            ->deleteJson('/api/meus-agendamentos/999')
            ->assertNotFound();
    }

    public function test_cliente_nao_apaga_agendamento_ja_concluido(): void
    {
        $barbearia = $this->criarBarbearia('dono@barbearia.com');
        ['usuario' => $usuario, 'cliente' => $cliente] = $this->criarCliente('maria@email.com');
        $agendamento = $this->criarAgendamento($barbearia, $cliente, '2030-01-10 10:00:00');
        $agendamento->update(['status' => 'concluido']);

        $this->actingAs($usuario)
            ->deleteJson("/api/meus-agendamentos/{$agendamento->id}")
            ->assertUnprocessable()
            ->assertJson(['message' => 'Não é possível cancelar um agendamento já concluído.']);

        // O histórico do atendimento é da barbearia: o cliente não pode sumir com ele
        $this->assertDatabaseHas('agendamentos', ['id' => $agendamento->id, 'status' => 'concluido']);
    }
}
