<?php

declare(strict_types=1);

namespace Tests\Unit\Domain;

use App\Domain\Cliente;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class ClienteTest extends TestCase
{
    public function test_deve_criar_cliente_valido(): void
    {
        $cliente = new Cliente(
            id: null,
            nome: 'Maria',
            telefone: '83988888888',
            email: 'maria@email.com'
        );

        $this->assertSame('Maria', $cliente->nome());
    }

    public function test_nao_deve_criar_cliente_com_email_invalido(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Cliente(
            id: null,
            nome: 'Maria',
            telefone: '83988888888',
            email: 'invalido'
        );
    }

    public function test_nao_deve_criar_cliente_com_nome_vazio(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Cliente(
            id: null,
            nome: '',
            telefone: '83988888888',
            email: 'maria@email.com'
        );
    }
}
