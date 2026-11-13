<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Infrastructure\Persistence\Eloquent\ProfissionalModel;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;

class ProfissionalController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $barbearia = $this->estabelecimentoDoDono($request);

        return response()->json(
            ProfissionalModel::where('barbearia_id', $barbearia->id)->with('servicos')->get()
        );
    }

    public function store(Request $request): JsonResponse
    {
        $barbearia = $this->estabelecimentoDoDono($request);

        $dados = $request->validate([
            'nome' => 'required|string|max:255',
            'servicos' => 'array',
            'servicos.*' => [
                'integer',
                Rule::exists('servicos', 'id')->where('barbearia_id', $barbearia->id),
            ],
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

    public function update(Request $request, int $id): JsonResponse
    {
        $barbearia = $this->estabelecimentoDoDono($request);
        $profissional = ProfissionalModel::where('barbearia_id', $barbearia->id)->findOrFail($id);

        $dados = $request->validate([
            'nome' => 'required|string|max:255',
            'servicos' => 'array',
            'servicos.*' => [
                'integer',
                Rule::exists('servicos', 'id')->where('barbearia_id', $barbearia->id),
            ],
        ]);

        $profissional->update(['nome' => $dados['nome']]);
        $profissional->servicos()->sync($dados['servicos'] ?? []);

        return response()->json($profissional->load('servicos'));
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $barbearia = $this->estabelecimentoDoDono($request);
        ProfissionalModel::where('barbearia_id', $barbearia->id)->findOrFail($id)->delete();

        return response()->json(null, 204);
    }
}
