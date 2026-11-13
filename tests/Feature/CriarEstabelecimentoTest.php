<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Infrastructure\Persistence\Eloquent\BarbeariaModel;
use App\Infrastructure\Persistence\Eloquent\ProfissionalModel;
use App\Infrastructure\Persistence\Eloquent\UserModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CriarEstabelecimentoTest extends TestCase
{
    use RefreshDatabase;

    public function test_cria_estabelecimento_dona_e_profissional(): void
    {
        $this->criar(senha: 'senha-segura-123')->assertSuccessful();

        $estabelecimento = BarbeariaModel::where('dominio', 'trancas.juliogabrieldev.com')->firstOrFail();
        $dona = UserModel::where('email', 'dona@girassol.com')->firstOrFail();

        $this->assertSame('Studio Girassol Beauty', $estabelecimento->nome);
        $this->assertSame($dona->id, $estabelecimento->user_id);
        $this->assertTrue(Hash::check('senha-segura-123', $dona->password));
        $this->assertSame(1, ProfissionalModel::where('barbearia_id', $estabelecimento->id)->count());
    }

    public function test_dona_criada_pelo_comando_entra_no_painel_pelo_proprio_endereco(): void
    {
        $this->criar(senha: 'senha-segura-123', dominio: 'localhost')->assertSuccessful();

        $this->postJson('/login-barbearia', [
            'email' => 'dona@girassol.com',
            'password' => 'senha-segura-123',
        ])->assertOk();
    }

    public function test_recusa_dominio_repetido_e_nao_cria_nada(): void
    {
        $this->criar(senha: 'senha-segura-123')->assertSuccessful();

        $this->criar(senha: 'senha-segura-123', email: 'outra@girassol.com')->assertFailed();

        $this->assertSame(1, BarbeariaModel::count());
        $this->assertSame(0, UserModel::where('email', 'outra@girassol.com')->count());
    }

    public function test_recusa_senha_curta(): void
    {
        $this->criar(senha: 'curta')->assertFailed();

        $this->assertSame(0, BarbeariaModel::count());
        $this->assertSame(0, UserModel::count());
    }

    private function criar(
        string $senha,
        string $dominio = 'trancas.juliogabrieldev.com',
        string $email = 'dona@girassol.com'
    ) {
        return $this->artisan('estabelecimento:criar', [
            '--nome' => 'Studio Girassol Beauty',
            '--dominio' => $dominio,
            '--email' => $email,
            '--profissional' => 'Dona do Studio',
            '--telefone' => '(83) 99999-0000',
            '--endereco' => 'Rua das Flores, 10',
        ])
            ->expectsQuestion('Senha de acesso da dona (mínimo 8 caracteres)', $senha)
            ->expectsQuestion('Repita a senha', $senha);
    }
}
