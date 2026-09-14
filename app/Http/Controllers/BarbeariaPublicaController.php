<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Infrastructure\Persistence\Eloquent\BarbeariaModel;
use Illuminate\Http\JsonResponse;

class BarbeariaPublicaController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(BarbeariaModel::all(['id', 'nome', 'endereco']));
    }

    public function show(int $id): JsonResponse
    {
        return response()->json(
            BarbeariaModel::with(['servicos', 'profissionais.servicos'])->findOrFail($id)
        );
    }
}
