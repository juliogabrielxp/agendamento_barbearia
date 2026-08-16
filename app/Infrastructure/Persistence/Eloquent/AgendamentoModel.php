<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgendamentoModel extends Model
{
    protected $table = 'agendamentos';

    protected $fillable = [
        'profissional_id',
        'cliente_id',
        'servico_id',
        'inicio',
    ];

    protected $casts = [
        'inicio' => 'datetime',
    ];

    public function servico(): BelongsTo
    {
        return $this->belongsTo(ServicoModel::class, 'servico_id');
    }
}
