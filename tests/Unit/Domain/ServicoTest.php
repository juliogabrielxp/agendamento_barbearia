<?php

declare(strict_types=1);

namespace Tests\Unit\Domain;

use App\Domain\Servico;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class ServicoTest extends TestCase
{
    public function test_nao_deve_criar_servico_com_duracao_invalida(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Servico(
            barbeariaId: 1,
            nome: 'Corte Masculino',
            duracaoEmMinutos: 0,
            preco: 50.0
        );
    }

    public function test_nao_deve_criar_servico_com_nome_vazio(): void
    {

         $this->expectException(InvalidArgumentException::class);

         new Servico(
            barbeariaId: 1,
            nome: '',
            duracaoEmMinutos: 30,
            preco: 50.0
        );
    }

    public function test_nao_deve_criar_servico_com_preco_negativo(): void
    {

         $this->expectException(InvalidArgumentException::class);

         new Servico(
            barbeariaId: 1,
            nome: 'Barba',
            duracaoEmMinutos: 40,
            preco: -50.0
        );
    }
}
