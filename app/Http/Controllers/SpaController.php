<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Entrega a SPA e o manifest do PWA com o nome do estabelecimento do host,
 * para o título da aba, a prévia do link e o app instalado no celular.
 */
final class SpaController extends Controller
{
    public function show(Request $request): View
    {
        return view('spa', ['estabelecimento' => $this->estabelecimento($request)]);
    }

    public function manifest(Request $request): JsonResponse
    {
        $estabelecimento = $this->estabelecimento($request);

        return response()->json([
            'name' => $estabelecimento->nome,
            'short_name' => mb_strimwidth($estabelecimento->nome, 0, 12, ''),
            'description' => "Agende seu horário no {$estabelecimento->nome}.",
            'id' => '/app',
            'start_url' => '/app',
            'scope' => '/',
            'display' => 'standalone',
            'background_color' => '#fafafa',
            'theme_color' => '#171717',
            'lang' => 'pt-BR',
            'icons' => [
                ['src' => '/icons/icon-192.png', 'sizes' => '192x192', 'type' => 'image/png', 'purpose' => 'any'],
                ['src' => '/icons/icon-512.png', 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'any'],
                ['src' => '/icons/icon-maskable-512.png', 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'maskable'],
            ],
        ], 200, ['Content-Type' => 'application/manifest+json'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
