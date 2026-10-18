<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Infrastructure\Persistence\Eloquent\BarbeariaModel;
use App\Infrastructure\Persistence\Eloquent\ServicoModel;
use App\Infrastructure\Storage\ArmazenadorDeImagens;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ImagemController extends Controller
{
    private const REGRAS = [
        'imagem' => 'required|image|mimes:jpg,jpeg,png,webp|max:2048',
    ];

    public function __construct(
        private readonly ArmazenadorDeImagens $armazenador
    ) {
    }

    public function barbearia(Request $request): JsonResponse
    {
        $barbearia = BarbeariaModel::where('user_id', $request->user()->id)->firstOrFail();
        $request->validate(self::REGRAS);

        $caminhoAntigo = $barbearia->imagem_path;

        $barbearia->update([
            'imagem_path' => $this->armazenador->salvar($request->file('imagem'), 'imagens/barbearias'),
        ]);

        $this->armazenador->remover($caminhoAntigo);

        return response()->json($barbearia);
    }

    public function servico(Request $request, int $id): JsonResponse
    {
        $barbearia = BarbeariaModel::where('user_id', $request->user()->id)->firstOrFail();
        $servico = ServicoModel::where('barbearia_id', $barbearia->id)->findOrFail($id);
        $request->validate(self::REGRAS);

        $caminhoAntigo = $servico->imagem_path;

        $servico->update([
            'imagem_path' => $this->armazenador->salvar($request->file('imagem'), 'imagens/servicos'),
        ]);

        $this->armazenador->remover($caminhoAntigo);

        return response()->json($servico);
    }
}
