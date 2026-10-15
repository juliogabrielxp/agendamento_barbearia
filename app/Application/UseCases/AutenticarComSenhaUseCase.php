<?php

declare(strict_types=1);

namespace App\Application\UseCases;

use App\Domain\Contracts\BarbeariaRepositoryInterface;
use App\Domain\Contracts\ClienteRepositoryInterface;
use App\Domain\Contracts\PasswordHasherInterface;
use App\Domain\Contracts\UserRepositoryInterface;
use App\Domain\Exceptions\CredenciaisInvalidasException;
use App\Domain\TipoDeConta;

final class AutenticarComSenhaUseCase
{
    public function __construct(
        private readonly UserRepositoryInterface $usuarios,
        private readonly ClienteRepositoryInterface $clientes,
        private readonly BarbeariaRepositoryInterface $barbearias,
        private readonly PasswordHasherInterface $hasher
    ) {
    }

    public function executar(AutenticarComSenhaInput $input): array
    {
        $usuario = $this->usuarios->buscarCredenciaisPorEmail($input->email);

        // Conta inexistente ou criada só pelo Google (sem senha)
        if ($usuario === null || $usuario['password_hash'] === null) {
            throw new CredenciaisInvalidasException();
        }

        if (!$this->hasher->verificar($input->senha, $usuario['password_hash'])) {
            throw new CredenciaisInvalidasException();
        }

        if (!$this->possuiPerfil($usuario['id'], $input->tipoDeConta)) {
            throw new CredenciaisInvalidasException();
        }

        unset($usuario['password_hash']);

        return $usuario;
    }

    private function possuiPerfil(int $userId, TipoDeConta $tipoDeConta): bool
    {
        return match ($tipoDeConta) {
            TipoDeConta::Cliente => $this->clientes->existeParaUsuario($userId),
            TipoDeConta::Barbearia => $this->barbearias->existeParaUsuario($userId),
        };
    }
}
