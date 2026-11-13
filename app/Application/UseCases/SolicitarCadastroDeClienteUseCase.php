<?php

declare(strict_types=1);

namespace App\Application\UseCases;

use App\Domain\CadastroPendente;
use App\Domain\Contracts\CadastroPendenteRepositoryInterface;
use App\Domain\Contracts\EnviadorDeCodigoDeConfirmacaoInterface;
use App\Domain\Contracts\PasswordHasherInterface;
use App\Domain\Contracts\RelogioInterface;
use App\Domain\Contracts\UserRepositoryInterface;
use App\Domain\Exceptions\EmailJaCadastradoException;
use App\Domain\Exceptions\EnvioDeEmailFalhouException;
use App\Domain\Exceptions\ReenvioDeCodigoMuitoCedoException;

/**
 * Primeiro passo do cadastro por e-mail: guarda os dados e manda o código.
 * A conta só nasce em ConfirmarCadastroDeClienteUseCase.
 */
final class SolicitarCadastroDeClienteUseCase
{
    public function __construct(
        private readonly UserRepositoryInterface $usuarios,
        private readonly CadastroPendenteRepositoryInterface $pendentes,
        private readonly EnviadorDeCodigoDeConfirmacaoInterface $enviador,
        private readonly PasswordHasherInterface $hasher,
        private readonly RelogioInterface $relogio
    ) {}

    /**
     * @return string chave que identifica este cadastro; fica só na sessão de quem o iniciou
     *
     * @throws EmailJaCadastradoException
     * @throws ReenvioDeCodigoMuitoCedoException
     * @throws EnvioDeEmailFalhouException
     */
    public function executar(SolicitarCadastroDeClienteInput $input): string
    {
        if ($this->usuarios->emailJaCadastrado($input->email)) {
            throw new EmailJaCadastradoException;
        }

        $agora = $this->relogio->agora();

        $anterior = $this->pendentes->buscarPorEmail($input->email);
        $espera = $anterior?->segundosParaPoderReenviar($agora) ?? 0;

        if ($espera > 0) {
            throw ReenvioDeCodigoMuitoCedoException::aguarde($espera);
        }

        $codigo = CadastroPendente::gerarCodigo();
        $chave = CadastroPendente::gerarChave();

        $cadastro = CadastroPendente::iniciar(
            $input->nome,
            $input->email,
            $this->hasher->hash($input->senha),
            $this->hasher->hash($codigo),
            $chave,
            $agora,
        );

        // Envia antes de gravar: se o e-mail falhar, nada muda e dá para tentar de novo na hora
        $this->enviador->enviar($input->email, $input->nome, $codigo, $input->nomeDoEstabelecimento);

        // Substitui um cadastro pendente anterior do mesmo e-mail (e invalida a chave dele)
        $this->pendentes->salvar($cadastro);

        return $chave;
    }
}
