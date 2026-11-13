<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Infrastructure\Persistence\Eloquent\BarbeariaModel;
use App\Infrastructure\Persistence\Eloquent\ClienteModel;
use App\Infrastructure\Persistence\Eloquent\UserModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as UsuarioGoogle;
use Tests\TestCase;

class LoginGoogleTest extends TestCase
{
    use RefreshDatabase;

    private function googleDevolve(string $googleId, string $email, string $nome = 'Maria', bool $emailVerificado = true): void
    {
        $usuario = (new UsuarioGoogle)
            ->setRaw(['email_verified' => $emailVerificado])
            ->map([
                'id' => $googleId,
                'name' => $nome,
                'email' => $email,
                'avatar' => 'https://exemplo.com/avatar.png',
            ]);

        Socialite::shouldReceive('driver->redirectUrl->user')->andReturn($usuario);
    }

    public function test_cliente_novo_entra_com_google(): void
    {
        $this->googleDevolve('google-1', 'maria@gmail.com');

        $this->get('/auth/google/cliente/callback')->assertRedirect('/app');

        $usuario = UserModel::where('google_id', 'google-1')->firstOrFail();
        $this->assertAuthenticatedAs($usuario);
        $this->assertDatabaseHas('clientes', ['user_id' => $usuario->id, 'email' => 'maria@gmail.com']);
    }

    public function test_google_nao_cria_estabelecimento_para_qualquer_conta(): void
    {
        // Antes existia /auth/google/barbearia, que criava um painel de dona para
        // qualquer conta Google. Estabelecimento novo agora só pelo comando artisan.
        $this->googleDevolve('google-2', 'curioso@gmail.com', 'Curioso');

        $this->get('/auth/google/barbearia/callback')->assertNotFound();

        $this->assertGuest();
        $this->assertSame(0, BarbeariaModel::count());
    }

    public function test_login_repetido_reaproveita_usuario_e_cliente(): void
    {
        $this->googleDevolve('google-1', 'maria@gmail.com');

        $this->get('/auth/google/cliente/callback');
        $this->get('/auth/google/cliente/callback');

        $this->assertSame(1, UserModel::where('google_id', 'google-1')->count());
        $this->assertSame(1, ClienteModel::count());
    }

    public function test_google_vincula_a_conta_com_senha_de_mesmo_email(): void
    {
        $existente = UserModel::create(['nome' => 'Maria', 'email' => 'maria@gmail.com', 'password' => 'senha-segura-123']);
        ClienteModel::create(['user_id' => $existente->id, 'nome' => 'Maria', 'email' => 'maria@gmail.com', 'telefone' => '']);
        $this->googleDevolve('google-1', 'maria@gmail.com');

        $this->get('/auth/google/cliente/callback')->assertRedirect('/app');

        $this->assertAuthenticatedAs($existente);
        $this->assertSame('google-1', $existente->fresh()->google_id);
        $this->assertSame(1, UserModel::count());
        $this->assertSame(1, ClienteModel::count());
    }

    public function test_google_com_email_nao_verificado_nao_assume_conta_existente(): void
    {
        $existente = UserModel::create(['nome' => 'Maria', 'email' => 'maria@gmail.com', 'password' => 'senha-segura-123']);
        $this->googleDevolve('google-invasor', 'maria@gmail.com', 'Invasor', emailVerificado: false);

        $this->get('/auth/google/cliente/callback')->assertRedirect('/app/entrar?erro=google-email-em-uso');

        $this->assertGuest();
        $this->assertNull($existente->fresh()->google_id);
    }

    public function test_login_google_troca_o_id_da_sessao(): void
    {
        // Fixação de sessão: o atacante planta um id de sessão conhecido no navegador
        // da vítima. Se o login não trocar o id, o atacante herda a sessão autenticada.
        $idPlantado = Str::random(40);
        $this->googleDevolve('google-1', 'maria@gmail.com');

        $this->withCookie(config('session.cookie'), $idPlantado)
            ->get('/auth/google/cliente/callback')
            ->assertRedirect('/app');

        $this->assertNotSame($idPlantado, app('session')->driver()->getId());
    }

    public function test_login_google_de_cliente_troca_o_token_csrf(): void
    {
        // O Auth::login já troca o id, mas só o regenerate() troca o token CSRF.
        // Sem isso, um token conhecido antes do login continua valendo depois dele.
        $this->googleDevolve('google-1', 'maria@gmail.com');

        $this->withSession(['_token' => 'token-de-antes-do-login'])
            ->get('/auth/google/cliente/callback')
            ->assertRedirect('/app');

        $this->assertNotSame('token-de-antes-do-login', app('session')->driver()->token());
    }
}
