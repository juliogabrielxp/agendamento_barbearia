<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Model;

class ClienteModel extends Model
{
    protected $table = 'clientes';

    protected $fillable = ['user_id', 'nome', 'telefone', 'email', 'observacoes'];
}
