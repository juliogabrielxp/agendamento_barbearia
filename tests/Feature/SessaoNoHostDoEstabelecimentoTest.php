<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\CriaCenarioDeAgenda;
use Tests\TestCase;

/**
 * Cada estabelecimento responde no próprio host. Mesmo com SANCTUM_STATEFUL_DOMAINS
 * fixado no .env, a sessão tem que valer no host da requisição, senão quem acessa
 * por um host fora da lista entra e toma 401 no /api/me.
 */
class SessaoNoHostDoEstabelecimentoTest extends TestCase
{
    use CriaCenarioDeAgenda;
    use RefreshDatabase;

    protected function setUp(): void
    {
        $_ENV['SANCTUM_STATEFUL_DOMAINS'] = $_SERVER['SANCTUM_STATEFUL_DOMAINS'] = '127.0.0.1:8000';

        parent::setUp();

        // Sessão gravada no banco e levada só pelo cookie, como num navegador
        config(['session.driver' => 'database']);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        unset($_ENV['SANCTUM_STATEFUL_DOMAINS'], $_SERVER['SANCTUM_STATEFUL_DOMAINS']);
    }

    public function test_sessao_vale_no_host_da_requisicao_mesmo_fora_da_lista_do_env(): void
    {
        $this->criarBarbearia('dona@estudio.com', 'trancas.test');
        ['usuario' => $usuario] = $this->criarCliente('maria@email.com');

        $login = $this->withHeader('Referer', 'http://trancas.test/app/entrar')
            ->postJson('http://trancas.test/login-cliente', [
                'email' => 'maria@email.com',
                'password' => 'senha-segura-123',
            ])
            ->assertOk();

        $nomeDoCookie = config('session.cookie');
        $idDaSessao = $login->getCookie($nomeDoCookie)->getValue();

        $this->esquecerEstadoEmMemoria();

        $this->withHeader('Referer', 'http://trancas.test/app/agendamentos')
            ->withCredentials()
            ->withCookie($nomeDoCookie, $idDaSessao)
            ->getJson('http://trancas.test/api/me')
            ->assertOk()
            ->assertJsonPath('user.email', $usuario->email);
    }

    /** Entre duas requisições de verdade, só o que está no banco e no cookie sobrevive. */
    private function esquecerEstadoEmMemoria(): void
    {
        $this->app['auth']->forgetGuards();
        $this->app['session']->forgetDrivers();
        $this->app->forgetInstance('session.store');
    }
}
