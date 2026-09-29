<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\Barbearia;
use App\Infrastructure\Persistence\Eloquent\BarbeariaModel;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use InvalidArgumentException;

class BarbeariaPerfilController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $barbearia = BarbeariaModel::where('user_id', $request->user()->id)->firstOrFail();

        return response()->json($barbearia);
    }

    public function update(Request $request): JsonResponse
    {
        $barbeariaModel = BarbeariaModel::where('user_id', $request->user()->id)->firstOrFail();

        $dados = $request->validate([
            'nome' => 'required|string|max:255',
            'telefone' => 'required|string|max:20',
            'email' => 'required|email|max:255',
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
