<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\CriaCenarioDeAgenda;
use Tests\TestCase;

/**
 * Serviço ou profissional que já aparece em algum agendamento não pode ser apagado
 * (a FK de agendamentos impede): a dona recebe 422 com o motivo, não 500.
 */
class ExclusaoComAgendamentoTest extends TestCase
{
    use CriaCenarioDeAgenda;
    use RefreshDatabase;

    public function test_servico_com_agendamento_nao_e_excluido(): void
    {
        $barbearia = $this->criarBarbearia('dona@estudio.com');
        ['cliente' => $cliente] = $this->criarCliente('maria@email.com');
        $this->criarAgendamento($barbearia, $cliente, '2030-01-10 10:00:00');

        $this->actingAs($barbearia['dono'])
            ->deleteJson("/api/servicos/{$barbearia['servico']->id}")
            ->assertUnprocessable()
            ->assertJson(['message' => 'Este serviço tem agendamentos e não pode ser excluído.']);

        $this->assertDatabaseHas('servicos', ['id' => $barbearia['servico']->id]);
        $this->assertDatabaseHas('profissional_servico', ['servico_id' => $barbearia['servico']->id]);
    }

    public function test_profissional_com_agendamento_nao_e_excluida(): void
    {
        $barbearia = $this->criarBarbearia('dona@estudio.com');
        ['cliente' => $cliente] = $this->criarCliente('maria@email.com');
        $this->criarAgendamento($barbearia, $cliente, '2030-01-10 10:00:00');

        $this->actingAs($barbearia['dono'])
            ->deleteJson("/api/profissionais/{$barbearia['profissional']->id}")
            ->assertUnprocessable()
            ->assertJson(['message' => 'Esta profissional tem agendamentos e não pode ser excluída.']);

        $this->assertDatabaseHas('profissionais', ['id' => $barbearia['profissional']->id]);
        $this->assertDatabaseHas('profissional_servico', ['profissional_id' => $barbearia['profissional']->id]);
    }

    public function test_sem_agendamento_a_exclusao_continua_funcionando(): void
    {
        $barbearia = $this->criarBarbearia('dona@estudio.com');

        $this->actingAs($barbearia['dono'])->deleteJson("/api/profissionais/{$barbearia['profissional']->id}")->assertNoContent();
        $this->actingAs($barbearia['dono'])->deleteJson("/api/servicos/{$barbearia['servico']->id}")->assertNoContent();

        $this->assertDatabaseMissing('servicos', ['id' => $barbearia['servico']->id]);
        $this->assertDatabaseMissing('profissionais', ['id' => $barbearia['profissional']->id]);
    }
}
