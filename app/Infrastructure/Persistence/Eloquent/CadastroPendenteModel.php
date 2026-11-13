<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Model;

class CadastroPendenteModel extends Model
{
    protected $table = 'cadastros_pendentes';

    protected $fillable = [
        'email',
        'nome',
        'senha_hash',
        'codigo_hash',
        'chave_hash',
        'tentativas',
        'expira_em',
        'enviado_em',
    ];

    protected $hidden = ['senha_hash', 'codigo_hash', 'chave_hash'];

    protected $casts = [
        'tentativas' => 'integer',
        'expira_em' => 'immutable_datetime',
        'enviado_em' => 'immutable_datetime',
    ];
}
