<?php

declare(strict_types=1);

namespace App\Domain;

use InvalidArgumentException;


class Servico
{
    public function __construct(
        private readonly string $nome,
        private readonly float $preco,
        private readonly int $barbeariaId,
        private readonly int $duracaoEmMinutos

    ) {
        $this->nomeVazio();
        $this->duracaoInvalida();
        $this->precoInvalido();
    }

    public function nomeVazio()
    {
        if (empty($this->nome)) {

            throw new InvalidArgumentException('Nome inválido');
        }
    }

    public function duracaoInvalida()
    {

        if ($this->duracaoEmMinutos < 1) {

            throw new InvalidArgumentException('Duração inválido');
        }
    }

     public function precoInvalido()
    {

        if ($this->preco < 0) {

            throw new InvalidArgumentException('Preço inválido');
        }
    }




}
