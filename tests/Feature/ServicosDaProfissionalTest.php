<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Infrastructure\Persistence\Eloquent\ServicoModel;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Concerns\CriaCenarioDeAgenda;
use Tests\TestCase;

/**
 * Os serviços enviados ao salvar uma profissional são conferidos numa consulta só,
 * e continuam precisando ser do próprio estabelecimento.
 */
class ServicosDaProfissionalTest extends TestCase
{
    use CriaCenarioDeAgenda;
    use RefreshDatabase;

    public function test_confere_oito_servicos_com_uma_consulta_so(): void
    {
        $barbearia = $this->criarBarbearia('dona@estudio.com');
        $ids = $this->criarServicos($barbearia, 8);
        $consultas = $this->contarConsultasQueValidamServicos();

        $this->actingAs($barbearia['dono'])
            ->postJson('/api/profissionais', ['nome' => 'Ana', 'servicos' => $ids])
            ->assertCreated();
        $this->assertSame(1, $consultas(), 'ao criar');

        $profissional = $barbearia['profissional'];
        $this->actingAs($barbearia['dono'])
            ->putJson("/api/profissionais/{$profissional->id}", ['nome' => 'João', 'servicos' => $ids])
            ->assertOk();
        $this->assertSame(2, $consultas(), 'ao editar');

        $this->assertSame(8, $profissional->servicos()->count());
    }

    public function test_aponta_qual_servico_nao_e_do_estabelecimento(): void
    {
        $minha = $this->criarBarbearia('dona@estudio.com');
        $outra = $this->criarBarbearia('vizinha@barbearia.com');
        [$meu] = $this->criarServicos($minha, 1);

        $this->actingAs($minha['dono'])
            ->postJson('/api/profissionais', [
                'nome' => 'Ana',
                'servicos' => [$meu, $outra['servico']->id, 999999],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['servicos.1', 'servicos.2'])
            ->assertJsonMissingValidationErrors(['servicos.0']);

        $this->assertDatabaseMissing('profissionais', ['nome' => 'Ana']);
    }

    public function test_id_que_nao_e_numero_continua_dando_422(): void
    {
        $barbearia = $this->criarBarbearia('dona@estudio.com');

        $this->actingAs($barbearia['dono'])
            ->postJson('/api/profissionais', ['nome' => 'Ana', 'servicos' => ['abc']])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['servicos.0']);
    }

    /** @return list<int> */
    private function criarServicos(array $barbearia, int $quantos): array
    {
        return array_map(fn (int $i) => ServicoModel::create([
            'barbearia_id' => $barbearia['barbearia']->id,
            'nome' => "Serviço {$i}",
            'duracao_em_minutos' => 30,
            'preco' => 40.0,
        ])->id, range(1, $quantos));
    }

    /**
     * Conta as consultas em "servicos" filtradas pelo estabelecimento: a validação.
     * O load('servicos') da resposta faz join com a pivô e não entra na conta.
     */
    private function contarConsultasQueValidamServicos(): \Closure
    {
        $total = 0;
        DB::listen(function (QueryExecuted $query) use (&$total): void {
            $sql = str_replace(['`', '"'], '', $query->sql);
            if (str_starts_with($sql, 'select') && str_contains($sql, 'from servicos where')) {
                $total++;
            }
        });

        return function () use (&$total): int {
            return $total;
        };
    }
}
