<?php

declare(strict_types=1);

namespace App\Application\UseCases;

use App\Domain\CadastroPendente;
use App\Domain\Contracts\CadastroPendenteRepositoryInterface;
use App\Domain\Contracts\EnviadorDeCodigoDeConfirmacaoInterface;
use App\Domain\Contracts\PasswordHasherInterface;
use App\Domain\Contracts\RelogioInterface;
use App\Domain\Exceptions\ConfirmacaoDeEmailException;
use App\Domain\Exceptions\EnvioDeEmailFalhouException;
use App\Domain\Exceptions\ReenvioDeCodigoMuitoCedoException;

final class ReenviarCodigoDeConfirmacaoUseCase
{
    public function __construct(
        private readonly CadastroPendenteRepositoryInterface $pendentes,
        private readonly EnviadorDeCodigoDeConfirmacaoInterface $enviador,
        private readonly PasswordHasherInterface $hasher,
        private readonly RelogioInterface $relogio
    ) {}

    /**
     * @throws ConfirmacaoDeEmailException
     * @throws ReenvioDeCodigoMuitoCedoException
     * @throws EnvioDeEmailFalhouException
     */
    public function executar(ReenviarCodigoDeConfirmacaoInput $input): void
    {
        $cadastro = $this->pendentes->buscarPorEmail($input->email);

        if ($cadastro === null || ! $cadastro->pertenceA($input->chave)) {
            throw ConfirmacaoDeEmailException::cadastroNaoEncontrado();
        }

        $agora = $this->relogio->agora();
        $espera = $cadastro->segundosParaPoderReenviar($agora);

        if ($espera > 0) {
            throw ReenvioDeCodigoMuitoCedoException::aguarde($espera);
        }

        $codigo = CadastroPendente::gerarCodigo();

        $this->enviador->enviar($cadastro->email, $cadastro->nome, $codigo, $input->nomeDoEstabelecimento);

        $this->pendentes->salvar($cadastro->comNovoCodigo($this->hasher->hash($codigo), $agora));
    }
}
