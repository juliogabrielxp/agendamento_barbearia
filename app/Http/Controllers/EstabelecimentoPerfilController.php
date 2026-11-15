<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\Estabelecimento;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use InvalidArgumentException;

class EstabelecimentoPerfilController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $estabelecimento = $this->estabelecimentoDoDono($request);

        return response()->json($estabelecimento);
    }

    public function update(Request $request): JsonResponse
    {
        $estabelecimentoModel = $this->estabelecimentoDoDono($request);

        $dados = $request->validate([
            'nome' => 'required|string|max:255',
            'telefone' => 'required|string|max:20',
            // estabelecimentos.email é unique: e-mail de outro estabelecimento vira 422, não 500
            'email' => ['required', 'email', 'max:255', Rule::unique('estabelecimentos', 'email')->ignore($estabelecimentoModel->id)],
            'endereco' => 'required|string|max:255',
        ]);

        try {
            new Estabelecimento(
                id: $estabelecimentoModel->id,
                nome: $dados['nome'],
                telefone: $dados['telefone'],
                email: $dados['email'],
                endereco: $dados['endereco'],
            );
        } catch (InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $estabelecimentoModel->update($dados);

        return response()->json($estabelecimentoModel);
    }
}
