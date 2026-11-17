<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Infrastructure\Persistence\Eloquent\ServicoModel;
use App\Infrastructure\Storage\ArmazenadorDeImagens;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ImagemController extends Controller
{
    private const REGRAS = [
        'imagem' => 'required|image|mimes:jpg,jpeg,png,webp|max:5120',
    ];

    public function __construct(
        private readonly ArmazenadorDeImagens $armazenador
    ) {}

    public function perfil(Request $request): JsonResponse
    {
        $estabelecimento = $this->estabelecimentoDoDono($request);
        $request->validate(self::REGRAS);

        $caminhoAntigo = $estabelecimento->imagem_path;

        $estabelecimento->update([
            'imagem_path' => $this->armazenador->salvar($request->file('imagem'), 'imagens/estabelecimentos'),
        ]);

        $this->armazenador->remover($caminhoAntigo);

        return response()->json($estabelecimento);
    }

    public function servico(Request $request, int $id): JsonResponse
    {
        $estabelecimento = $this->estabelecimentoDoDono($request);
        $servico = ServicoModel::where('estabelecimento_id', $estabelecimento->id)->findOrFail($id);
        $request->validate(self::REGRAS);

        $caminhoAntigo = $servico->imagem_path;

        $servico->update([
            'imagem_path' => $this->armazenador->salvar($request->file('imagem'), 'imagens/servicos'),
        ]);

        $this->armazenador->remover($caminhoAntigo);

        return response()->json($servico);
    }
}
