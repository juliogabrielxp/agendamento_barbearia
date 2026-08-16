<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Model;

class BarbeariaModel extends Model
{
    protected $table = 'barbearias';

    protected $fillable = ['user_id','nome', 'telefone', 'email', 'endereco'];
}
