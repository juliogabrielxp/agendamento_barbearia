<?php

declare(strict_types=1);

namespace App\Application\UseCases;

use App\Domain\Contracts\UserRepositoryInterface;

final class AutenticarComGoogleUseCase
{
    public function __construct(
        private readonly UserRepositoryInterface $repositorio
    ) {
    }

    public function executar(AutenticarComGoogleInput $input): array
    {
        $usuarioExistente = $this->repositorio->buscarPorGoogleId($input->googleId);

        if ($usuarioExistente !== null) {
            return $usuarioExistente;
        }

        return $this->repositorio->criar(
            $input->googleId,
            $input->nome,
            $input->email,
            $input->avatar
        );
    }
}
