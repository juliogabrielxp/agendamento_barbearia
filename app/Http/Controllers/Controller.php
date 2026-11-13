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
}
