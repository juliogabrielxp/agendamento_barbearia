<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent;

use App\Infrastructure\Persistence\Eloquent\Concerns\TemImagem;
use Illuminate\Database\Eloquent\Model;

class ServicoModel extends Model
{
    use TemImagem;

    protected $appends = ['imagem_url'];

    protected $hidden = ['imagem_path'];

    protected $table = 'servicos';

    protected $fillable = ['barbearia_id', 'nome', 'duracao_em_minutos', 'preco', 'pode_atravessar_intervalo', 'imagem_path'];

    protected $attributes = [
        'pode_atravessar_intervalo' => false,
    ];

    protected $casts = [
        'pode_atravessar_intervalo' => 'boolean',
    ];
}
