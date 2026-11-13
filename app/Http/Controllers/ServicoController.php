<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Infrastructure\Persistence\Eloquent\BarbeariaModel;
use App\Infrastructure\Persistence\Eloquent\ProfissionalModel;
use App\Infrastructure\Persistence\Eloquent\ServicoModel;
use App\Infrastructure\Storage\ArmazenadorDeImagens;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ServicoController extends Controller
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

        $dados = $request->validate($this->regras());

        $servico = ServicoModel::create([...$dados, 'barbearia_id' => $barbearia->id]);

        // Com uma profissional só, o serviço novo já é dela: sem isso ele
        // ficaria fora da vitrine até a dona vinculá-lo na aba Profissionais.
        $profissionais = ProfissionalModel::where('barbearia_id', $barbearia->id)->get();
        if ($profissionais->count() === 1) {
            $profissionais->first()->servicos()->attach($servico->id);
        }

        return response()->json($servico, 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $barbearia = BarbeariaModel::where('user_id', $request->user()->id)->firstOrFail();
        $servico = ServicoModel::where('barbearia_id', $barbearia->id)->findOrFail($id);

        $dados = $request->validate($this->regras());

        $servico->update($dados);

        return response()->json($servico);
    }

    public function destroy(Request $request, int $id, ArmazenadorDeImagens $armazenador): JsonResponse
    {
        $barbearia = BarbeariaModel::where('user_id', $request->user()->id)->firstOrFail();
        $servico = ServicoModel::where('barbearia_id', $barbearia->id)->findOrFail($id);

        $servico->delete();
        $armazenador->remover($servico->imagem_path);

        return response()->json(null, 204);
    }

    private function regras(): array
    {
        return [
            'nome' => 'required|string|max:255',
            'duracao_em_minutos' => 'required|integer|min:1',
            'preco' => 'required|numeric|min:0',
            'pode_atravessar_intervalo' => 'sometimes|boolean',
        ];
    }
}
