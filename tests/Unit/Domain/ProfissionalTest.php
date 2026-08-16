<?php

declare(strict_types=1);

namespace Tests\Unit\Domain;

use App\Domain\Profissional;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class ProfissionalTest extends TestCase
{
    public function test_nao_deve_criar_profissional_com_nome_vazio(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Profissional(
            barbeariaId: 1,
            nome: '',
            servicosIds: [1, 2]
        );
    }

    public function test_deve_criar_profissional_valido(): void
    {
        $profissional = new Profissional(
            barbeariaId: 1,
            nome: 'João',
            servicosIds: [1, 2]
        );

        $this->assertSame('João', $profissional->nome());
    }
}
