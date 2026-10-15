<?php

declare(strict_types=1);

namespace App\Application\UseCases;

use App\Domain\Contracts\ClienteRepositoryInterface;
use App\Domain\Contracts\PasswordHasherInterface;
use App\Domain\Contracts\TransacaoInterface;
use App\Domain\Contracts\UserRepositoryInterface;
use App\Domain\Exceptions\EmailJaCadastradoException;

final class CadastrarClienteUseCase
{
    public function __construct(
        private readonly UserRepositoryInterface $usuarios,
        private readonly ClienteRepositoryInterface $clientes,
        private readonly PasswordHasherInterface $hasher,
        private readonly TransacaoInterface $transacao
    ) {
    }

    public function executar(CadastrarClienteInput $input): array
    {
        if ($this->usuarios->emailJaCadastrado($input->email)) {
            throw new EmailJaCadastradoException();
        }

        $senhaHash = $this->hasher->hash($input->senha);

        return $this->transacao->executar(function () use ($input, $senhaHash): array {
            $usuario = $this->usuarios->criarComSenha($input->nome, $input->email, $senhaHash);

            $this->clientes->criarParaUsuario($usuario['id'], $input->nome, $input->email);

            return $usuario;
        });
    }
}
