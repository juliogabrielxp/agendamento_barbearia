<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Infrastructure\Persistence\Eloquent\BarbeariaModel;
use App\Infrastructure\Persistence\Eloquent\ServicoModel;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ServicoController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $barbearia = BarbeariaModel::where('user_id', $request->user()->id)->firstOrFail();

        return response()->json(
            ServicoModel::where('barbearia_id', $barbearia->id)->get()
        );
    }

    public function store(Request $request): JsonResponse
    {
        $barbearia = BarbeariaModel::where('user_id', $request->user()->id)->firstOrFail();

        $dados = $request->validate([
            'nome' => 'required|string|max:255',
            'duracao_em_minutos' => 'required|integer|min:1',
            'preco' => 'required|numeric|min:0',
        ]);

        $servico = ServicoModel::create([...$dados, 'barbearia_id' => $barbearia->id]);

        return response()->json($servico, 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $barbearia = BarbeariaModel::where('user_id', $request->user()->id)->firstOrFail();
        $servico = ServicoModel::where('barbearia_id', $barbearia->id)->findOrFail($id);

        $dados = $request->validate([
            'nome' => 'required|string|max:255',
            'duracao_em_minutos' => 'required|integer|min:1',
            'preco' => 'required|numeric|min:0',
        ]);

        $servico->update($dados);

        return response()->json($servico);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $barbearia = BarbeariaModel::where('user_id', $request->user()->id)->firstOrFail();
        ServicoModel::where('barbearia_id', $barbearia->id)->findOrFail($id)->delete();

        return response()->json(null, 204);
    }
}
