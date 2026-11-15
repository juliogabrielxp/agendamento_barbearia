<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Infrastructure\Persistence\Eloquent\EstabelecimentoModel;
use App\Infrastructure\Persistence\Eloquent\ProfissionalModel;
use App\Infrastructure\Persistence\Eloquent\ServicoModel;
use App\Infrastructure\Persistence\Eloquent\UserModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class EdicaoDeCadastrosTest extends TestCase
{
    use RefreshDatabase;

    public function test_dono_edita_nome_e_servicos_do_profissional(): void
    {
        $estabelecimento = $this->criarEstabelecimentoComDono('dono@estabelecimento.com');
        $corte = $this->criarServico($estabelecimento, 'Corte');
        $barba = $this->criarServico($estabelecimento, 'Barba');

        $profissional = ProfissionalModel::create(['estabelecimento_id' => $estabelecimento->id, 'nome' => 'João']);
        $profissional->servicos()->attach([$corte->id]);

        $this->actingAs(UserModel::find($estabelecimento->user_id));

        $response = $this->putJson("/api/profissionais/{$profissional->id}", [
            'nome' => 'João Silva',
            'servicos' => [$barba->id],
        ]);

        $this->assertSame(200, $response->getStatusCode(), $response->getContent());
        $this->assertDatabaseHas('profissionais', ['id' => $profissional->id, 'nome' => 'João Silva']);
        $this->assertDatabaseHas('profissional_servico', ['profissional_id' => $profissional->id, 'servico_id' => $barba->id]);
        $this->assertDatabaseMissing('profissional_servico', ['profissional_id' => $profissional->id, 'servico_id' => $corte->id]);
    }

    public function test_dono_nao_edita_profissional_de_outra_estabelecimento(): void
    {
        $minha = $this->criarEstabelecimentoComDono('minha@estabelecimento.com');
        $outra = $this->criarEstabelecimentoComDono('outra@estabelecimento.com');
        $profissionalDaOutra = ProfissionalModel::create(['estabelecimento_id' => $outra->id, 'nome' => 'Carlos']);

        $this->actingAs(UserModel::find($minha->user_id));

        $response = $this->putJson("/api/profissionais/{$profissionalDaOutra->id}", ['nome' => 'Invasor']);

        $this->assertSame(404, $response->getStatusCode(), $response->getContent());
        $this->assertDatabaseHas('profissionais', ['id' => $profissionalDaOutra->id, 'nome' => 'Carlos']);
    }

    public function test_dono_nao_vincula_servico_de_outra_estabelecimento_ao_profissional(): void
    {
        $minha = $this->criarEstabelecimentoComDono('minha@estabelecimento.com');
        $outra = $this->criarEstabelecimentoComDono('outra@estabelecimento.com');
        $servicoDaOutra = $this->criarServico($outra, 'Corte da Outra');
        $profissional = ProfissionalModel::create(['estabelecimento_id' => $minha->id, 'nome' => 'João']);

        $this->actingAs(UserModel::find($minha->user_id));

        $response = $this->putJson("/api/profissionais/{$profissional->id}", [
            'nome' => 'João',
            'servicos' => [$servicoDaOutra->id],
        ]);

        $this->assertSame(422, $response->getStatusCode(), $response->getContent());
        $this->assertDatabaseMissing('profissional_servico', ['servico_id' => $servicoDaOutra->id]);
    }

    public function test_dono_nao_vincula_servico_de_outra_estabelecimento_ao_criar_profissional(): void
    {
        $minha = $this->criarEstabelecimentoComDono('minha@estabelecimento.com');
        $outra = $this->criarEstabelecimentoComDono('outra@estabelecimento.com');
        $servicoDaOutra = $this->criarServico($outra, 'Corte da Outra');

        $this->actingAs(UserModel::find($minha->user_id));

        $response = $this->postJson('/api/profissionais', [
            'nome' => 'João',
            'servicos' => [$servicoDaOutra->id],
        ]);

        $this->assertSame(422, $response->getStatusCode(), $response->getContent());
        $this->assertDatabaseMissing('profissional_servico', ['servico_id' => $servicoDaOutra->id]);
        $this->assertDatabaseMissing('profissionais', ['estabelecimento_id' => $minha->id]);
    }

    public function test_dono_nao_edita_servico_de_outra_estabelecimento(): void
    {
        $minha = $this->criarEstabelecimentoComDono('minha@estabelecimento.com');
        $outra = $this->criarEstabelecimentoComDono('outra@estabelecimento.com');
        $servicoDaOutra = $this->criarServico($outra, 'Corte da Outra');

        $this->actingAs(UserModel::find($minha->user_id));

        $response = $this->putJson("/api/servicos/{$servicoDaOutra->id}", [
            'nome' => 'Invasor',
            'duracao_em_minutos' => 10,
            'preco' => 1,
        ]);

        $this->assertSame(404, $response->getStatusCode(), $response->getContent());
        $this->assertDatabaseHas('servicos', ['id' => $servicoDaOutra->id, 'nome' => 'Corte da Outra']);
    }

    private function criarEstabelecimentoComDono(string $email): EstabelecimentoModel
    {
        $user = UserModel::create(['nome' => 'Dono', 'email' => $email, 'password' => 'senha-segura-123']);

        return EstabelecimentoModel::create([
            'user_id' => $user->id,
            // A primeira responde no host padrão dos testes; as outras ficam em outro endereço
            'dominio' => EstabelecimentoModel::where('dominio', 'localhost')->exists() ? Str::slug($email).'.test' : 'localhost',
            'nome' => 'Estabelecimento '.$email,
            'email' => $email,
            'telefone' => '',
            'endereco' => 'Rua Teste, 1',
        ]);
    }

    private function criarServico(EstabelecimentoModel $estabelecimento, string $nome): ServicoModel
    {
        return ServicoModel::create([
            'estabelecimento_id' => $estabelecimento->id,
            'nome' => $nome,
            'duracao_em_minutos' => 30,
            'preco' => 40.0,
        ]);
    }
}
