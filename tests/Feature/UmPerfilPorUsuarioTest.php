<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Infrastructure\Persistence\Eloquent\BarbeariaModel;
use App\Infrastructure\Persistence\Eloquent\ClienteModel;
use App\Infrastructure\Persistence\Eloquent\UserModel;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * O código busca o perfil com `where('user_id', ...)->first()`. Com dois perfis
 * para o mesmo usuário, qual deles volta é arbitrário. O banco precisa impedir isso,
 * inclusive quando dois logins Google simultâneos rodam o `firstOrCreate` ao mesmo tempo.
 */
class UmPerfilPorUsuarioTest extends TestCase
{
    use RefreshDatabase;

    public function test_usuario_nao_tem_dois_perfis_de_cliente(): void
    {
        $usuario = UserModel::create(['nome' => 'Maria', 'email' => 'maria@email.com', 'password' => 'senha-segura-123']);
        ClienteModel::create(['user_id' => $usuario->id, 'nome' => 'Maria', 'email' => 'maria@email.com', 'telefone' => '']);

        $this->expectException(UniqueConstraintViolationException::class);

        ClienteModel::create(['user_id' => $usuario->id, 'nome' => 'Maria 2', 'email' => 'outro@email.com', 'telefone' => '']);
    }

    public function test_usuario_nao_tem_duas_barbearias(): void
    {
        $dono = UserModel::create(['nome' => 'Dono', 'email' => 'dono@email.com', 'password' => 'senha-segura-123']);
        BarbeariaModel::create(['user_id' => $dono->id, 'nome' => 'A', 'email' => 'a@email.com', 'telefone' => '', 'endereco' => '']);

        $this->expectException(UniqueConstraintViolationException::class);

        BarbeariaModel::create(['user_id' => $dono->id, 'nome' => 'B', 'email' => 'b@email.com', 'telefone' => '', 'endereco' => '']);
    }

    public function test_usuario_pode_ser_cliente_e_dono_de_barbearia(): void
    {
        $usuario = UserModel::create(['nome' => 'Ana', 'email' => 'ana@email.com', 'password' => 'senha-segura-123']);

        ClienteModel::create(['user_id' => $usuario->id, 'nome' => 'Ana', 'email' => 'ana@email.com', 'telefone' => '']);
        BarbeariaModel::create(['user_id' => $usuario->id, 'nome' => 'Ana Barber', 'email' => 'ana@email.com', 'telefone' => '', 'endereco' => '']);

        $this->assertDatabaseCount('clientes', 1);
        $this->assertDatabaseCount('barbearias', 1);
    }
}
