<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Infrastructure\Persistence\Eloquent\BarbeariaModel;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Cada estabelecimento responde no próprio host (ex.: trancas.juliogabrieldev.com).
 * O tenant da requisição vem daqui, nunca de um id enviado pelo cliente.
 */
final class IdentificaEstabelecimento
{
    public const ATRIBUTO = 'estabelecimento';

    public function handle(Request $request, Closure $next): Response
    {
        $estabelecimento = BarbeariaModel::where('dominio', strtolower($request->getHost()))->first();

        abort_if($estabelecimento === null, 404, 'Estabelecimento não encontrado.');

        $request->attributes->set(self::ATRIBUTO, $estabelecimento);

        return $next($request);
    }
}
