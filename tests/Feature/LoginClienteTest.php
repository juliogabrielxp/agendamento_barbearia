<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\CriaCenarioDeAgenda;
use Tests\TestCase;

class LoginClienteTest extends TestCase
{
    use RefreshDatabase;
    use CriaCenarioDeAgenda;

    public function test_cliente_entra_com_email_e_senha(): void
    {
        ['usuario' => $usuario] = $this->criarCliente('maria@email.com');

        $response = $this->postJson('/login-cliente', [
            'email' => 'maria@email.com',
            'password' => 'senha-segura-123',
        ]);

        $this->assertSame(200, $response->getStatusCode(), $response->getContent());
        $this->assertAuthenticatedAs($usuario);
    }

    public function test_nao_entra_na_conta_de_outro_cliente_com_senha_errada(): void
    {
        $this->criarCliente('maria@email.com');

        $response = $this->postJson('/login-cliente', [
            'email' => 'maria@email.com',
            'password' => 'senha-de-outra-pessoa',
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors('email');
        $this->assertGuest();
    }

    public function test_dono_de_barbearia_nao_entra_pela_tela_do_cliente(): void
    {
        // Senha correta, mas a conta não tem perfil de cliente
        $this->criarBarbearia('dono@barbearia.com');

        $response = $this->postJson('/login-cliente', [
            'email' => 'dono@barbearia.com',
            'password' => 'senha-segura-123',
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors('email');
        $this->assertGuest();
    }
}
