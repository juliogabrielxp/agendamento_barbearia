<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Infrastructure\Persistence\Eloquent\BarbeariaModel;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class BarbeariaConfiguracaoController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $barbearia = BarbeariaModel::where('user_id', $request->user()->id)->firstOrFail();

        return response()->json($barbearia);
    }

    public function update(Request $request): JsonResponse
    {
        $barbearia = BarbeariaModel::where('user_id', $request->user()->id)->firstOrFail();

        $dados = $request->validate([
            'hora_abertura' => 'required|date_format:H:i',
            'hora_fechamento' => 'required|date_format:H:i|after:hora_abertura',
            'intervalo_inicio' => 'nullable|date_format:H:i|after_or_equal:hora_abertura',
            'intervalo_fim' => 'nullable|date_format:H:i|after:intervalo_inicio|before_or_equal:hora_fechamento',
        ]);

        if (($dados['intervalo_inicio'] ?? null) && !($dados['intervalo_fim'] ?? null)) {
            return response()->json(['errors' => ['intervalo_fim' => ['Informe o fim do intervalo.']]], 422);
        }

        $barbearia->update($dados);

        return response()->json($barbearia);
    }
}
