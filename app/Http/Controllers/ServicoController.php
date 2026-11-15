<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Infrastructure\Persistence\Eloquent\ProfissionalModel;
use App\Infrastructure\Persistence\Eloquent\ServicoModel;
use App\Infrastructure\Storage\ArmazenadorDeImagens;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final class ServicoController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $estabelecimento = $this->estabelecimentoDoDono($request);

        return response()->json(
            ServicoModel::where('estabelecimento_id', $estabelecimento->id)->get()
        );
    }

    public function store(Request $request): JsonResponse
    {
        $estabelecimento = $this->estabelecimentoDoDono($request);

        $dados = $request->validate($this->regras());

        $servico = DB::transaction(function () use ($dados, $estabelecimento) {
            $servico = ServicoModel::create([...$dados, 'estabelecimento_id' => $estabelecimento->id]);

            // Com uma profissional só, o serviço novo já é dela: sem isso ele
            // ficaria fora da vitrine até a dona vinculá-lo na aba Profissionais.
            $profissionais = ProfissionalModel::where('estabelecimento_id', $estabelecimento->id)->get();
            if ($profissionais->count() === 1) {
                $profissionais->first()->servicos()->attach($servico->id);
            }

            return $servico;
        });

        return response()->json($servico, 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $estabelecimento = $this->estabelecimentoDoDono($request);
        $servico = ServicoModel::where('estabelecimento_id', $estabelecimento->id)->findOrFail($id);

        $dados = $request->validate($this->regras());

        $servico->update($dados);

        return response()->json($servico);
    }

    public function destroy(Request $request, int $id, ArmazenadorDeImagens $armazenador): JsonResponse
    {
        $estabelecimento = $this->estabelecimentoDoDono($request);
        $servico = ServicoModel::where('estabelecimento_id', $estabelecimento->id)->findOrFail($id);

        $recusa = $this->excluirOuRecusar($servico, 'Este serviço tem agendamentos e não pode ser excluído.');
        if ($recusa) {
            return $recusa;
        }

        $armazenador->remover($servico->imagem_path);

        return response()->json(null, 204);
    }

    private function regras(): array
    {
        return [
            'nome' => 'required|string|max:255',
            'duracao_em_minutos' => 'required|integer|min:1',
            'preco' => 'required|numeric|min:0|max:999999.99', // a coluna é decimal(8,2)
            'pode_atravessar_intervalo' => 'sometimes|boolean',
        ];
    }
}
