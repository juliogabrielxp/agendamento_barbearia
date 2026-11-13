<?php

namespace App\Http\Controllers;

use App\Http\Middleware\IdentificaEstabelecimento;
use App\Infrastructure\Persistence\Eloquent\BarbeariaModel;
use Illuminate\Http\Request;
use LogicException;

abstract class Controller
{
    /** Estabelecimento do host da requisição; exige o middleware "estabelecimento" na rota */
    protected function estabelecimento(Request $request): BarbeariaModel
    {
        $estabelecimento = $request->attributes->get(IdentificaEstabelecimento::ATRIBUTO);

        if (! $estabelecimento instanceof BarbeariaModel) {
            throw new LogicException('Rota sem o middleware "estabelecimento".');
        }

        return $estabelecimento;
    }

    /**
     * Estabelecimento do host, desde que o usuário logado seja o dono dele.
     * Dona logada no endereço de outro estabelecimento recebe 404: o painel só
     * abre no próprio domínio, venha a sessão do login por senha ou do Google.
     */
    protected function estabelecimentoDoDono(Request $request): BarbeariaModel
    {
        $estabelecimento = $this->estabelecimento($request);

        abort_unless((int) $estabelecimento->user_id === (int) $request->user()?->id, 404);

        return $estabelecimento;
    }
}
