<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;

class CadastroPendenteModel extends Model
{
    use MassPrunable;

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

    /**
     * Cadastro nunca confirmado guarda nome, e-mail e hash de senha (LGPD): sai um dia
     * depois de expirar. Rodado pelo model:prune agendado em routes/console.php.
     */
    public function prunable(): Builder
    {
        return static::where('expira_em', '<', now()->subDay());
    }
}
