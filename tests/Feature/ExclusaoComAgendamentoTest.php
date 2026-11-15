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
        $estabelecimento = $this->criarEstabelecimento('dona@estudio.com');
        ['cliente' => $cliente] = $this->criarCliente('maria@email.com');
        $this->criarAgendamento($estabelecimento, $cliente, '2030-01-10 10:00:00');

        $this->actingAs($estabelecimento['dono'])
            ->deleteJson("/api/servicos/{$estabelecimento['servico']->id}")
            ->assertUnprocessable()
            ->assertJson(['message' => 'Este serviço tem agendamentos e não pode ser excluído.']);

        $this->assertDatabaseHas('servicos', ['id' => $estabelecimento['servico']->id]);
        $this->assertDatabaseHas('profissional_servico', ['servico_id' => $estabelecimento['servico']->id]);
    }

    public function test_profissional_com_agendamento_nao_e_excluida(): void
    {
        $estabelecimento = $this->criarEstabelecimento('dona@estudio.com');
        ['cliente' => $cliente] = $this->criarCliente('maria@email.com');
        $this->criarAgendamento($estabelecimento, $cliente, '2030-01-10 10:00:00');

        $this->actingAs($estabelecimento['dono'])
            ->deleteJson("/api/profissionais/{$estabelecimento['profissional']->id}")
            ->assertUnprocessable()
            ->assertJson(['message' => 'Esta profissional tem agendamentos e não pode ser excluída.']);

        $this->assertDatabaseHas('profissionais', ['id' => $estabelecimento['profissional']->id]);
        $this->assertDatabaseHas('profissional_servico', ['profissional_id' => $estabelecimento['profissional']->id]);
    }

    public function test_sem_agendamento_a_exclusao_continua_funcionando(): void
    {
        $estabelecimento = $this->criarEstabelecimento('dona@estudio.com');

        $this->actingAs($estabelecimento['dono'])->deleteJson("/api/profissionais/{$estabelecimento['profissional']->id}")->assertNoContent();
        $this->actingAs($estabelecimento['dono'])->deleteJson("/api/servicos/{$estabelecimento['servico']->id}")->assertNoContent();

        $this->assertDatabaseMissing('servicos', ['id' => $estabelecimento['servico']->id]);
        $this->assertDatabaseMissing('profissionais', ['id' => $estabelecimento['profissional']->id]);
    }
}
