<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Infrastructure\Persistence\Eloquent\BarbeariaModel;
use App\Infrastructure\Persistence\Eloquent\ProfissionalModel;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ProfissionalController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $barbearia = BarbeariaModel::where('user_id', $request->user()->id)->firstOrFail();

        return response()->json(
            ProfissionalModel::where('barbearia_id', $barbearia->id)->with('servicos')->get()
        );
    }

    public function store(Request $request): JsonResponse
    {
        $barbearia = BarbeariaModel::where('user_id', $request->user()->id)->firstOrFail();

        $dados = $request->validate([
            'nome' => 'required|string|max:255',
            'servicos' => 'array',
            'servicos.*' => 'integer|exists:servicos,id',
        ]);

        $profissional = ProfissionalModel::create([
            'nome' => $dados['nome'],
            'barbearia_id' => $barbearia->id,
        ]);

        if (!empty($dados['servicos'])) {
            $profissional->servicos()->attach($dados['servicos']);
        }

        return response()->json($profissional->load('servicos'), 201);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $barbearia = BarbeariaModel::where('user_id', $request->user()->id)->firstOrFail();
        ProfissionalModel::where('barbearia_id', $barbearia->id)->findOrFail($id)->delete();

        return response()->json(null, 204);
    }
}
