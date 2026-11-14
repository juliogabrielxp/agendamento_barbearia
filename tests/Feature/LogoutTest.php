<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\Feature\Concerns\CriaCenarioDeAgenda;
use Tests\TestCase;

/**
 * Logout como num navegador: a sessão fica no banco e só o cookie passa de uma
 * requisição para a outra. Depois do logout, o cookie antigo não abre mais nada.
 */
class LogoutTest extends TestCase
{
    use CriaCenarioDeAgenda;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['session.driver' => 'database']);
    }

    public function test_cookie_do_cliente_nao_vale_mais_depois_do_logout(): void
    {
        $this->criarBarbearia('dona@estudio.com');
        $this->criarCliente('maria@email.com');
        $cookie = $this->entrar('/login-cliente', 'maria@email.com');

        $this->comCookie($cookie)->getJson('/api/meus-agendamentos')->assertOk();

        $this->comCookie($cookie)->postJson('/api/logout')->assertOk();

        $this->comCookie($cookie)->getJson('/api/meus-agendamentos')->assertUnauthorized();
    }

    public function test_cookie_da_dona_nao_abre_mais_o_painel_depois_do_logout(): void
    {
        $this->criarBarbearia('dona@estudio.com');
        $cookie = $this->entrar('/login-barbearia', 'dona@estudio.com');

        $this->comCookie($cookie)->getJson('/api/servicos')->assertOk();

        $this->comCookie($cookie)->postJson('/api/logout')->assertOk();

        $this->comCookie($cookie)->getJson('/api/servicos')->assertUnauthorized();
    }

    public function test_logout_sem_login_responde_401(): void
    {
        $this->criarBarbearia('dona@estudio.com');

        $this->withHeader('Referer', 'http://localhost/app')
            ->postJson('/api/logout')
            ->assertUnauthorized();
    }

    /** Faz login pela rota e devolve o id da sessão que foi para o cookie */
    private function entrar(string $rota, string $email): string
    {
        /** @var TestResponse $login */
        $login = $this->withHeader('Referer', 'http://localhost/app/entrar')
            ->postJson($rota, ['email' => $email, 'password' => 'senha-segura-123'])
            ->assertOk();

        return $login->getCookie(config('session.cookie'))->getValue();
    }

    /** Nova requisição com só o cookie: esquece o usuário e a sessão que ficaram em memória */
    private function comCookie(string $idDaSessao): static
    {
        $this->app['auth']->forgetGuards();
        $this->app['session']->forgetDrivers();
        $this->app->forgetInstance('session.store');

        return $this->withHeader('Referer', 'http://localhost/app')
            ->withCredentials()
            ->withCookie(config('session.cookie'), $idDaSessao);
    }
}
