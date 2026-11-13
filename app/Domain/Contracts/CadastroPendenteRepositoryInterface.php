<?php

declare(strict_types=1);

namespace App\Domain\Contracts;

use App\Domain\CadastroPendente;

interface CadastroPendenteRepositoryInterface
{
    public function buscarPorEmail(string $email): ?CadastroPendente;

    /** Igual a buscarPorEmail, mas trava a linha até o fim da transação */
    public function travarPorEmail(string $email): ?CadastroPendente;

    /** Cria ou substitui o cadastro pendente daquele e-mail */
    public function salvar(CadastroPendente $cadastro): void;

    public function registrarTentativaErrada(string $email): void;

    public function remover(string $email): void;
}
