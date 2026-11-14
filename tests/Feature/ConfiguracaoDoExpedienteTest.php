<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\CriaCenarioDeAgenda;
use Tests\TestCase;

/**
 * O intervalo do expediente tem os dois horários ou nenhum: só o início ou só o fim
 * gravaria um intervalo pela metade, que a agenda não sabe interpretar.
 */
class ConfiguracaoDoExpedienteTest extends TestCase
{
    use CriaCenarioDeAgenda;
    use RefreshDatabase;

    private const EXPEDIENTE = ['hora_abertura' => '08:00', 'hora_fechamento' => '19:00'];

    public function test_recusa_fim_do_intervalo_sem_o_inicio(): void
    {
        $barbearia = $this->criarBarbearia('dona@estudio.com');

        $this->actingAs($barbearia['dono'])
            ->putJson('/api/configuracao-barbearia', [...self::EXPEDIENTE, 'intervalo_inicio' => null, 'intervalo_fim' => '13:00'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['intervalo_inicio' => 'Informe o início do intervalo.']);

        $this->assertDatabaseHas('barbearias', ['id' => $barbearia['barbearia']->id, 'intervalo_fim' => null]);
    }

    public function test_recusa_inicio_do_intervalo_sem_o_fim(): void
    {
        $barbearia = $this->criarBarbearia('dona@estudio.com');

        $this->actingAs($barbearia['dono'])
            ->putJson('/api/configuracao-barbearia', [...self::EXPEDIENTE, 'intervalo_inicio' => '12:00', 'intervalo_fim' => null])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['intervalo_fim' => 'Informe o fim do intervalo.']);
    }

    public function test_aceita_intervalo_completo_ou_nenhum(): void
    {
        $barbearia = $this->criarBarbearia('dona@estudio.com');

        $this->actingAs($barbearia['dono'])
            ->putJson('/api/configuracao-barbearia', [...self::EXPEDIENTE, 'intervalo_inicio' => '12:00', 'intervalo_fim' => '13:00'])
            ->assertOk();

        $this->actingAs($barbearia['dono'])
            ->putJson('/api/configuracao-barbearia', [...self::EXPEDIENTE, 'intervalo_inicio' => null, 'intervalo_fim' => null])
            ->assertOk();

        $this->assertDatabaseHas('barbearias', ['id' => $barbearia['barbearia']->id, 'intervalo_inicio' => null, 'intervalo_fim' => null]);
    }
}
