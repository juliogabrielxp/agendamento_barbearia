<?php

declare(strict_types=1);

namespace App\Infrastructure\Storage;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class ArmazenadorDeImagens
{
    public function salvar(UploadedFile $arquivo, string $pasta): string
    {
        // putFile gera um nome aleatório: o nome enviado pelo usuário nunca é usado
        $caminho = $this->disco()->putFile($pasta, $arquivo);

        if ($caminho === false) {
            throw new RuntimeException('Não foi possível salvar a imagem.');
        }

        return $caminho;
    }

    public function remover(?string $caminho): void
    {
        if ($caminho !== null && $caminho !== '') {
            $this->disco()->delete($caminho);
        }
    }

    private function disco(): Filesystem
    {
        return Storage::disk(config('filesystems.imagens_disk'));
    }
}
