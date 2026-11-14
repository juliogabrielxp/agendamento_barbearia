<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\Barbearia;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use InvalidArgumentException;

class BarbeariaPerfilController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $barbearia = $this->estabelecimentoDoDono($request);

        return response()->json($barbearia);
    }

    public function update(Request $request): JsonResponse
    {
        $barbeariaModel = $this->estabelecimentoDoDono($request);

        $dados = $request->validate([
            'nome' => 'required|string|max:255',
            'telefone' => 'required|string|max:20',
            // barbearias.email é unique: e-mail de outro estabelecimento vira 422, não 500
            'email' => ['required', 'email', 'max:255', Rule::unique('barbearias', 'email')->ignore($barbeariaModel->id)],
            'endereco' => 'required|string|max:255',
        ]);

        try {
            new Barbearia(
                id: $barbeariaModel->id,
                nome: $dados['nome'],
                telefone: $dados['telefone'],
                email: $dados['email'],
                endereco: $dados['endereco'],
            );
        } catch (InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $barbeariaModel->update($dados);

        return response()->json($barbeariaModel);
    }
}
