<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EstabelecimentoConfiguracaoController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $estabelecimento = $this->estabelecimentoDoDono($request);

        return response()->json($estabelecimento);
    }

    public function update(Request $request): JsonResponse
    {
        $estabelecimento = $this->estabelecimentoDoDono($request);

        // Intervalo: os dois horários ou nenhum
        $dados = $request->validate([
            'hora_abertura' => 'required|date_format:H:i',
            'hora_fechamento' => 'required|date_format:H:i|after:hora_abertura',
            'intervalo_inicio' => 'nullable|required_with:intervalo_fim|date_format:H:i|after_or_equal:hora_abertura',
            'intervalo_fim' => 'nullable|required_with:intervalo_inicio|date_format:H:i|after:intervalo_inicio|before_or_equal:hora_fechamento',
        ], [
            'intervalo_inicio.required_with' => 'Informe o início do intervalo.',
            'intervalo_fim.required_with' => 'Informe o fim do intervalo.',
        ]);

        $estabelecimento->update($dados);

        return response()->json($estabelecimento);
    }
}
