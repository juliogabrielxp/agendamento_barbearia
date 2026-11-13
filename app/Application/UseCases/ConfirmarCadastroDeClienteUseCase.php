<?php

declare(strict_types=1);

namespace App\Application\UseCases;

use App\Domain\Contracts\CadastroPendenteRepositoryInterface;
use App\Domain\Contracts\ClienteRepositoryInterface;
use App\Domain\Contracts\PasswordHasherInterface;
use App\Domain\Contracts\RelogioInterface;
use App\Domain\Contracts\TransacaoInterface;
use App\Domain\Contracts\UserRepositoryInterface;
use App\Domain\Exceptions\ConfirmacaoDeEmailException;
use App\Domain\Exceptions\EmailJaCadastradoException;
use DomainException;

/**
 * Segundo passo do cadastro por e-mail: confere o código e só então cria usuário e cliente.
 */
final class ConfirmarCadastroDeClienteUseCase
{
    public function __construct(
        private readonly UserRepositoryInterface $usuarios,
        private readonly ClienteRepositoryInterface $clientes,
        private readonly CadastroPendenteRepositoryInterface $pendentes,
        private readonly PasswordHasherInterface $hasher,
        private readonly TransacaoInterface $transacao,
        private readonly RelogioInterface $relogio
    ) {}

    /**
     * @throws ConfirmacaoDeEmailException
     * @throws EmailJaCadastradoException
     */
    public function executar(ConfirmarCadastroDeClienteInput $input): array
    {
        $agora = $this->relogio->agora();

        // A tentativa errada precisa ser gravada. Se a exceção fosse lançada dentro da
        // transação, o rollback apagaria a contagem e o limite de tentativas não valeria.
        // Por isso o erro sai da transação como valor e só é lançado depois do commit.
        $resultado = $this->transacao->executar(function () use ($input, $agora): array|DomainException {
            $cadastro = $this->pendentes->travarPorEmail($input->email);

            if ($cadastro === null || ! $cadastro->pertenceA($input->chave)) {
                return ConfirmacaoDeEmailException::cadastroNaoEncontrado();
            }

            if ($cadastro->tentativasEsgotadas()) {
                return ConfirmacaoDeEmailException::tentativasEsgotadas();
            }

            if ($cadastro->expirou($agora)) {
                return ConfirmacaoDeEmailException::codigoExpirado();
            }

            if (! $this->hasher->verificar($input->codigo, $cadastro->codigoHash)) {
                $this->pendentes->registrarTentativaErrada($cadastro->email);

                return ConfirmacaoDeEmailException::codigoIncorreto();
            }

            $this->pendentes->remover($cadastro->email);

            // Alguém pode ter entrado com Google usando este e-mail enquanto o código estava pendente
            if ($this->usuarios->emailJaCadastrado($cadastro->email)) {
                return new EmailJaCadastradoException;
            }

            $usuario = $this->usuarios->criarComSenha($cadastro->nome, $cadastro->email, $cadastro->senhaHash);

            $this->clientes->criarParaUsuario($usuario['id'], $cadastro->nome, $cadastro->email);

            return $usuario;
        });

        if ($resultado instanceof DomainException) {
            throw $resultado;
        }

        return $resultado;
    }
}
