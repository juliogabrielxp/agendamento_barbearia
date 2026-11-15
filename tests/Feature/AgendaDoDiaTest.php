<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Concerns\CriaCenarioDeAgenda;
use Tests\TestCase;

/**
 * GET /api/agenda?data=AAAA-MM-DD devolve os agendamentos daquele dia, filtrando
 * "inicio" por intervalo para o índice (profissional_id, inicio) ser usado inteiro.
 */
class AgendaDoDiaTest extends TestCase
{
    use CriaCenarioDeAgenda;
    use RefreshDatabase;

    public function test_traz_o_dia_inteiro_e_nada_do_dia_seguinte(): void
    {
        $estabelecimento = $this->criarEstabelecimento('dona@estudio.com');
        ['cliente' => $cliente] = $this->criarCliente('maria@email.com');
        $this->criarAgendamento($estabelecimento, $cliente, '2030-01-09 23:30:00');
        $meiaNoite = $this->criarAgendamento($estabelecimento, $cliente, '2030-01-10 00:00:00');
        $ultimo = $this->criarAgendamento($estabelecimento, $cliente, '2030-01-10 23:30:00');
        $this->criarAgendamento($estabelecimento, $cliente, '2030-01-11 00:00:00');

        $response = $this->actingAs($estabelecimento['dono'])->getJson('/api/agenda?data=2030-01-10');

        $response->assertOk();
        $this->assertSame([$meiaNoite->id, $ultimo->id], array_column($response->json(), 'id'));
    }

    public function test_filtra_inicio_por_intervalo_sem_funcao_na_coluna(): void
    {
        $estabelecimento = $this->criarEstabelecimento('dona@estudio.com');

        $consultas = [];
        DB::listen(function (QueryExecuted $query) use (&$consultas): void {
            if (str_contains($query->sql, 'from "agendamentos"') || str_contains($query->sql, 'from `agendamentos`')) {
                $consultas[] = $query->sql;
            }
        });

        $this->actingAs($estabelecimento['dono'])->getJson('/api/agenda?data=2030-01-10')->assertOk();

        $this->assertCount(1, $consultas);
        $this->assertDoesNotMatchRegularExpression('/(date|strftime)\s*\(/i', $consultas[0]);
        $this->assertMatchesRegularExpression('/["`]inicio["`]\s*>=\s*\?/', $consultas[0]);
        $this->assertMatchesRegularExpression('/["`]inicio["`]\s*<\s*\?/', $consultas[0]);
    }

    public function test_sem_data_ou_com_data_vazia_mostra_hoje(): void
    {
        $this->travelTo('2030-01-10 15:00:00');
        $estabelecimento = $this->criarEstabelecimento('dona@estudio.com');
        ['cliente' => $cliente] = $this->criarCliente('maria@email.com');
        $hoje = $this->criarAgendamento($estabelecimento, $cliente, '2030-01-10 18:00:00');
        $this->criarAgendamento($estabelecimento, $cliente, '2030-01-11 09:00:00');

        foreach (['/api/agenda', '/api/agenda?data='] as $url) {
            $response = $this->actingAs($estabelecimento['dono'])->getJson($url);

            $response->assertOk();
            $this->assertSame([$hoje->id], array_column($response->json(), 'id'), $url);
        }
    }

    public function test_data_invalida_devolve_422(): void
    {
        $estabelecimento = $this->criarEstabelecimento('dona@estudio.com');

        foreach (['abc', '2030-13-01', '10/01/2030', '2030-01-10 00:00:00'] as $data) {
            $this->actingAs($estabelecimento['dono'])
                ->getJson('/api/agenda?data='.urlencode($data))
                ->assertUnprocessable()
                ->assertJsonValidationErrors('data');
        }
    }
}
