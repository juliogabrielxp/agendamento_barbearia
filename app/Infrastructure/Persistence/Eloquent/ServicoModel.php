<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Model;

class ServicoModel extends Model
{
    protected $table = 'servicos';

    protected $fillable = ['barbearia_id', 'nome', 'duracao_em_minutos', 'preco'];
}
