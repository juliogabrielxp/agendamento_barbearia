<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Infrastructure\Persistence\Eloquent\BarbeariaModel;
use App\Infrastructure\Persistence\Eloquent\ProfissionalModel;
use App\Infrastructure\Persistence\Eloquent\ServicoModel;
use App\Infrastructure\Persistence\Eloquent\UserModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EdicaoDeCadastrosTest extends TestCase
{
    use RefreshDatabase;

    public function test_dono_edita_nome_e_servicos_do_profissional(): void
    {
        $barbearia = $this->criarBarbeariaComDono('dono@barbearia.com');
        $corte = $this->criarServico($barbearia, 'Corte');
        $barba = $this->criarServico($barbearia, 'Barba');

        $profissional = ProfissionalModel::create(['barbearia_id' => $barbearia->id, 'nome' => 'João']);
        $profissional->servicos()->attach([$corte->id]);

        $this->actingAs(UserModel::find($barbearia->user_id));

        $response = $this->putJson("/api/profissionais/{$profissional->id}", [
            'nome' => 'João Silva',
            'servicos' => [$barba->id],
        ]);

        $this->assertSame(200, $response->getStatusCode(), $response->getContent());
        $this->assertDatabaseHas('profissionais', ['id' => $profissional->id, 'nome' => 'João Silva']);
        $this->assertDatabaseHas('profissional_servico', ['profissional_id' => $profissional->id, 'servico_id' => $barba->id]);
        $this->assertDatabaseMissing('profissional_servico', ['profissional_id' => $profissional->id, 'servico_id' => $corte->id]);
    }

    public function test_dono_nao_edita_profissional_de_outra_barbearia(): void
    {
        $minha = $this->criarBarbeariaComDono('minha@barbearia.com');
        $outra = $this->criarBarbeariaComDono('outra@barbearia.com');
        $profissionalDaOutra = ProfissionalModel::create(['barbearia_id' => $outra->id, 'nome' => 'Carlos']);

        $this->actingAs(UserModel::find($minha->user_id));

        $response = $this->putJson("/api/profissionais/{$profissionalDaOutra->id}", ['nome' => 'Invasor']);

        $this->assertSame(404, $response->getStatusCode(), $response->getContent());
        $this->assertDatabaseHas('profissionais', ['id' => $profissionalDaOutra->id, 'nome' => 'Carlos']);
    }

    public function test_dono_nao_vincula_servico_de_outra_barbearia_ao_profissional(): void
    {
        $minha = $this->criarBarbeariaComDono('minha@barbearia.com');
        $outra = $this->criarBarbeariaComDono('outra@barbearia.com');
        $servicoDaOutra = $this->criarServico($outra, 'Corte da Outra');
        $profissional = ProfissionalModel::create(['barbearia_id' => $minha->id, 'nome' => 'João']);

        $this->actingAs(UserModel::find($minha->user_id));

        $response = $this->putJson("/api/profissionais/{$profissional->id}", [
            'nome' => 'João',
            'servicos' => [$servicoDaOutra->id],
        ]);

        $this->assertSame(422, $response->getStatusCode(), $response->getContent());
        $this->assertDatabaseMissing('profissional_servico', ['servico_id' => $servicoDaOutra->id]);
    }

    public function test_dono_nao_edita_servico_de_outra_barbearia(): void
    {
        $minha = $this->criarBarbeariaComDono('minha@barbearia.com');
        $outra = $this->criarBarbeariaComDono('outra@barbearia.com');
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

    private function criarBarbeariaComDono(string $email): BarbeariaModel
    {
        $user = UserModel::create(['nome' => 'Dono', 'email' => $email, 'password' => 'senha-segura-123']);

        return BarbeariaModel::create([
            'user_id' => $user->id,
            'nome' => 'Barbearia ' . $email,
            'email' => $email,
            'telefone' => '',
            'endereco' => 'Rua Teste, 1',
        ]);
    }

    private function criarServico(BarbeariaModel $barbearia, string $nome): ServicoModel
    {
        return ServicoModel::create([
            'barbearia_id' => $barbearia->id,
            'nome' => $nome,
            'duracao_em_minutos' => 30,
            'preco' => 40.0,
        ]);
    }
}
