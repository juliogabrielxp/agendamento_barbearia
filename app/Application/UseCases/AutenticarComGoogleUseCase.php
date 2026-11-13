<?php

declare(strict_types=1);

namespace App\Application\UseCases;

use App\Domain\Contracts\UserRepositoryInterface;
use App\Domain\Exceptions\EmailJaCadastradoException;

final class AutenticarComGoogleUseCase
{
    public function __construct(
        private readonly UserRepositoryInterface $repositorio
    ) {}

    /**
     * @throws EmailJaCadastradoException quando o e-mail já tem conta e o Google não garante que ele é do usuário
     */
    public function executar(AutenticarComGoogleInput $input): array
    {
        $usuarioExistente = $this->repositorio->buscarPorGoogleId($input->googleId);

        if ($usuarioExistente !== null) {
            return $usuarioExistente;
        }

        $mesmoEmail = $this->repositorio->buscarPorEmail($input->email);

        if ($mesmoEmail !== null) {
            // Só vincula se o Google confirmou o e-mail; senão, qualquer conta Google
            // com um e-mail não verificado assumiria a conta de outra pessoa.
            if (! $input->emailVerificado) {
                throw new EmailJaCadastradoException;
            }

            return $this->repositorio->vincularGoogle($mesmoEmail['id'], $input->googleId, $input->avatar);
        }

        return $this->repositorio->criar(
            $input->googleId,
            $input->nome,
            $input->email,
            $input->avatar
        );
    }
}
