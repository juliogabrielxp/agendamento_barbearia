<?php

declare(strict_types=1);

namespace Tests\Unit\Domain;

use App\Domain\Barbearia;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class BarbeariaTest extends TestCase
{
    public function test_deve_criar_barbearia_valida(): void
    {
        $barbearia = new Barbearia(
            id: null,
            nome: 'Barbearia do Zé',
            telefone: '83999999999',
            email: 'ze@barbearia.com',
            endereco: 'Rua A, 123'
        );

        $this->assertSame('Barbearia do Zé', $barbearia->nome());
    }

    public function test_nao_deve_criar_barbearia_com_email_invalido(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Barbearia(
            id: null,
            nome: 'Barbearia do Zé',
            telefone: '83999999999',
            email: 'email-invalido',
            endereco: 'Rua A, 123'
        );
    }

    public function test_nao_deve_criar_barbearia_com_endereco_vazio(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Barbearia(
            id: null,
            nome: 'Barbearia do Zé',
            telefone: '83999999999',
            email: 'ze@barbearia.com',
            endereco: ''
        );
    }
}
