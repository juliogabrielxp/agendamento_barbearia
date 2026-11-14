<?php

namespace App\Http\Controllers;

use App\Http\Middleware\IdentificaEstabelecimento;
use App\Infrastructure\Persistence\Eloquent\BarbeariaModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
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

    /**
     * Apaga o registro, ou devolve 422 com $motivo se ele ainda é referenciado por um
     * agendamento. A FK é quem decide: um agendamento criado no mesmo instante também conta.
     */
    protected function excluirOuRecusar(Model $registro, string $motivo): ?JsonResponse
    {
        try {
            $registro->delete();
        } catch (QueryException $e) {
            // 23000 num DELETE é a FK recusando (MySQL 1451, SQLite "FOREIGN KEY constraint failed")
            if ($e->getCode() !== '23000') {
                throw $e;
            }

            return response()->json(['message' => $motivo], 422);
        }

        return null;
    }
}
