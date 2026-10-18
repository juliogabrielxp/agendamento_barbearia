<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Model;
use App\Infrastructure\Persistence\Eloquent\Concerns\TemImagem;

class ServicoModel extends Model
{
    use TemImagem;

    protected $appends = ['imagem_url'];

    protected $hidden = ['imagem_path'];

    protected $table = 'servicos';

    protected $fillable = ['barbearia_id', 'nome', 'duracao_em_minutos', 'preco', 'imagem_path'];
}
