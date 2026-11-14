<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\CriaCenarioDeAgenda;
use Tests\TestCase;

/**
 * "Meus horários" vem de 20 em 20, do mais distante no futuro para o mais antigo,
 * e a próxima página continua de onde a anterior parou (cursor), sem repetir nem pular.
 */
class MeusAgendamentosPaginadosTest extends TestCase
{
    use CriaCenarioDeAgenda;
    use RefreshDatabase;

    public function test_vem_de_20_em_20_sem_repetir_nem_pular(): void
    {
        $barbearia = $this->criarBarbearia('dona@estudio.com');
        ['usuario' => $usuario, 'cliente' => $cliente] = $this->criarCliente('maria@email.com');

        $ids = [];
        foreach (range(1, 24) as $dia) {
            $ids[] = $this->criarAgendamento($barbearia, $cliente, sprintf('2030-01-%02d 10:00:00', $dia))->id;
        }
        // Mesmo horário de outro: o id desempata e nenhum dos dois some na virada de página
        $ids[] = $this->criarAgendamento($barbearia, $cliente, '2030-01-05 10:00:00')->id;

        $primeira = $this->actingAs($usuario)->getJson('/api/meus-agendamentos')->assertOk();
        $this->assertCount(20, $primeira->json('data'));
        $this->assertNotNull($primeira->json('next_cursor'));
        $this->assertSame('2030-01-24', substr($primeira->json('data.0.inicio'), 0, 10));

        $segunda = $this->actingAs($usuario)
            ->getJson('/api/meus-agendamentos?cursor='.$primeira->json('next_cursor'))
            ->assertOk();
        $this->assertCount(5, $segunda->json('data'));
        $this->assertNull($segunda->json('next_cursor'));

        $vistos = [...array_column($primeira->json('data'), 'id'), ...array_column($segunda->json('data'), 'id')];
        $this->assertSame(count($vistos), count(array_unique($vistos)));
        $this->assertEqualsCanonicalizing($ids, $vistos);
    }

    public function test_cada_pagina_continua_so_com_os_agendamentos_do_proprio_cliente(): void
    {
        $barbearia = $this->criarBarbearia('dona@estudio.com');
        ['usuario' => $maria, 'cliente' => $clienteMaria] = $this->criarCliente('maria@email.com');
        ['cliente' => $clienteAna] = $this->criarCliente('ana@email.com');
        $daMaria = $this->criarAgendamento($barbearia, $clienteMaria, '2030-01-10 10:00:00');
        $this->criarAgendamento($barbearia, $clienteAna, '2030-01-11 10:00:00');

        $this->actingAs($maria)->getJson('/api/meus-agendamentos')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $daMaria->id)
            ->assertJsonPath('next_cursor', null);
    }
}
