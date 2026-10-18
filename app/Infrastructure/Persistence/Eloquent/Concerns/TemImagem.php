<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Concerns;

use Illuminate\Support\Facades\Storage;

trait TemImagem
{
    public function getImagemUrlAttribute(): ?string
    {
        if (!$this->imagem_path) {
            return null;
        }

        return Storage::disk(config('filesystems.imagens_disk'))->url($this->imagem_path);
    }
}
