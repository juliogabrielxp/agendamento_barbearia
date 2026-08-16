<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ProfissionalModel extends Model
{
    protected $table = 'profissionais';

    protected $fillable = ['barbearia_id', 'nome'];

    public function servicos(): BelongsToMany
    {
        return $this->belongsToMany(
            ServicoModel::class,
            'profissional_servico',
            'profissional_id',
            'servico_id'
        );
    }
}
