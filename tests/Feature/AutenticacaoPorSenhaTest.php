<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Infrastructure\Persistence\Eloquent\BarbeariaModel;
use App\Infrastructure\Persistence\Eloquent\ClienteModel;
use App\Infrastructure\Persistence\Eloquent\UserModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AutenticacaoPorSenhaTest extends TestCase
{
    use RefreshDatabase;

    public function test_cadastro_de_cliente_cria_usuario_e_cliente_com_senha_valida(): void
    {
        $this->postJson('/cadastro-cliente', [
            'nome' => 'Maria Silva',
            'email' => 'maria@email.com',
            'password' => 'senha-segura-123',
            'password_confirmation' => 'senha-segura-123',
        ])->assertCreated();

        $this->assertDatabaseHas('clientes', ['email' => 'maria@email.com']);

        $hashSalvo = UserModel::where('email', 'maria@email.com')->value('password');
        $this->assertTrue(Hash::check('senha-segura-123', $hashSalvo));
    }

    public function test_cadastro_com_email_repetido_retorna_422(): void
    {
        UserModel::create(['nome' => 'Maria', 'email' => 'maria@email.com', 'password' => 'outra-senha-123']);

        $response = $this->postJson('/cadastro-cliente', [
            'nome' => 'Maria Silva',
            'email' => 'maria@email.com',
            'password' => 'senha-segura-123',
            'password_confirmation' => 'senha-segura-123',
        ]);

        $this->assertSame(422, $response->getStatusCode(), $response->getContent());
        $this->assertArrayHasKey('email', $response->json('errors'));
    }

    public function test_barbearia_entra_com_email_e_senha(): void
    {
        $user = UserModel::create(['nome' => 'Dono', 'email' => 'dono@barbearia.com', 'password' => 'senha-segura-123']);
        BarbeariaModel::create([
            'user_id' => $user->id,
            'nome' => 'Barbearia Teste',
            'email' => 'dono@barbearia.com',
            'telefone' => '',
            'endereco' => 'Rua Teste, 1',
        ]);

        $this->postJson('/login-barbearia', [
            'email' => 'dono@barbearia.com',
            'password' => 'senha-segura-123',
        ])->assertOk();
    }

    public function test_cliente_nao_consegue_entrar_pela_tela_da_barbearia(): void
    {
        $user = UserModel::create(['nome' => 'Maria', 'email' => 'maria@email.com', 'password' => 'senha-segura-123']);
        ClienteModel::create([
            'user_id' => $user->id,
            'nome' => 'Maria',
            'email' => 'maria@email.com',
            'telefone' => '',
        ]);

        $response = $this->postJson('/login-barbearia', [
            'email' => 'maria@email.com',
            'password' => 'senha-segura-123',
        ]);

        $this->assertSame(422, $response->getStatusCode(), $response->getContent());
    }
}
