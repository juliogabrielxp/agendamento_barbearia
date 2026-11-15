<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Infrastructure\Persistence\Eloquent\EstabelecimentoModel;
use App\Infrastructure\Persistence\Eloquent\ProfissionalModel;
use App\Infrastructure\Persistence\Eloquent\ServicoModel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProfissionalController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $estabelecimento = $this->estabelecimentoDoDono($request);

        return response()->json(
            ProfissionalModel::where('estabelecimento_id', $estabelecimento->id)->with('servicos')->get()
        );
    }

    public function store(Request $request): JsonResponse
    {
        $estabelecimento = $this->estabelecimentoDoDono($request);

        $dados = $this->validar($request, $estabelecimento);

        // Cadastro e vínculos juntos: se a segunda escrita falhar, a profissional não fica sem serviços
        $profissional = DB::transaction(function () use ($dados, $estabelecimento) {
            $profissional = ProfissionalModel::create([
                'nome' => $dados['nome'],
                'estabelecimento_id' => $estabelecimento->id,
            ]);

            if (! empty($dados['servicos'])) {
                $profissional->servicos()->attach($dados['servicos']);
            }

            return $profissional;
        });

        return response()->json($profissional->load('servicos'), 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $estabelecimento = $this->estabelecimentoDoDono($request);
        $profissional = ProfissionalModel::where('estabelecimento_id', $estabelecimento->id)->findOrFail($id);

        $dados = $this->validar($request, $estabelecimento);

        // O sync apaga os vínculos que saíram antes de gravar os que entraram: tudo ou nada
        DB::transaction(function () use ($profissional, $dados) {
            $profissional->update(['nome' => $dados['nome']]);
            $profissional->servicos()->sync($dados['servicos'] ?? []);
        });

        return response()->json($profissional->load('servicos'));
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $estabelecimento = $this->estabelecimentoDoDono($request);
        $profissional = ProfissionalModel::where('estabelecimento_id', $estabelecimento->id)->findOrFail($id);

        return $this->excluirOuRecusar($profissional, 'Esta profissional tem agendamentos e não pode ser excluída.')
            ?? response()->json(null, 204);
    }

    /**
     * Os serviços precisam ser deste estabelecimento. Confere todos numa consulta só
     * (Rule::exists fazia um SELECT por serviço) e aponta o erro em servicos.N, como antes.
     */
    private function validar(Request $request, EstabelecimentoModel $estabelecimento): array
    {
        $dados = $request->validate([
            'nome' => 'required|string|max:255',
            'servicos' => 'array',
            'servicos.*' => 'integer',
        ]);

        $ids = array_map('intval', $dados['servicos'] ?? []);
        if ($ids === []) {
            return $dados;
        }

        $doEstabelecimento = ServicoModel::where('estabelecimento_id', $estabelecimento->id)
            ->whereIn('id', array_unique($ids))
            ->pluck('id')
            ->all();

        $erros = [];
        foreach ($ids as $indice => $id) {
            if (! in_array($id, $doEstabelecimento, true)) {
                $erros["servicos.{$indice}"] = __('validation.exists', ['attribute' => "servicos.{$indice}"]);
            }
        }

        if ($erros !== []) {
            throw ValidationException::withMessages($erros);
        }

        return $dados;
    }
}
