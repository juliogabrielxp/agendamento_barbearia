<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Infrastructure\Persistence\Eloquent\ServicoModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\CriaCenarioDeAgenda;
use Tests\Feature\Concerns\SimulaQuedaNoMeio;
use Tests\TestCase;

/**
 * Profissional e serviço gravam em duas tabelas (o cadastro e o vínculo em profissional_servico).
 * Se o servidor cair entre as duas escritas, nada pode ficar pela metade.
 */
class GravacaoAtomicaDeCadastrosTest extends TestCase
{
    use CriaCenarioDeAgenda;
    use RefreshDatabase;
    use SimulaQuedaNoMeio;

    public function test_servico_novo_nao_fica_gravado_sem_o_vinculo_com_a_profissional(): void
    {
        $estabelecimento = $this->criarEstabelecimento('dona@estudio.com');
        $this->cairDepoisDe('insert into servicos');

        $this->actingAs($estabelecimento['dono'])->postJson('/api/servicos', [
            'nome' => 'Box braids',
            'duracao_em_minutos' => 240,
            'preco' => 250,
        ])->assertServerError();

        $this->assertDatabaseMissing('servicos', ['nome' => 'Box braids']);
    }

    public function test_profissional_nova_nao_fica_gravada_sem_os_servicos(): void
    {
        $estabelecimento = $this->criarEstabelecimento('dona@estudio.com');
        $this->cairDepoisDe('insert into profissionais');

        $this->actingAs($estabelecimento['dono'])->postJson('/api/profissionais', [
            'nome' => 'Ana',
            'servicos' => [$estabelecimento['servico']->id],
        ])->assertServerError();

        $this->assertDatabaseMissing('profissionais', ['nome' => 'Ana']);
    }

    public function test_edicao_da_profissional_nao_perde_os_servicos_antigos_no_meio_do_sync(): void
    {
        $estabelecimento = $this->criarEstabelecimento('dona@estudio.com');
        $profissional = $estabelecimento['profissional'];
        $corte = $estabelecimento['servico'];
        $barba = ServicoModel::create([
            'estabelecimento_id' => $estabelecimento['estabelecimento']->id,
            'nome' => 'Barba',
            'duracao_em_minutos' => 30,
            'preco' => 30.0,
        ]);
        $this->cairDepoisDe('delete from profissional_servico');

        $this->actingAs($estabelecimento['dono'])->putJson("/api/profissionais/{$profissional->id}", [
            'nome' => 'João Silva',
            'servicos' => [$barba->id],
        ])->assertServerError();

        $this->assertDatabaseHas('profissionais', ['id' => $profissional->id, 'nome' => 'João']);
        $this->assertDatabaseHas('profissional_servico', ['profissional_id' => $profissional->id, 'servico_id' => $corte->id]);
        $this->assertDatabaseMissing('profissional_servico', ['profissional_id' => $profissional->id, 'servico_id' => $barba->id]);
    }
}
