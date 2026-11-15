<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ProfissionalModel extends Model
{
    protected $table = 'profissionais';

    protected $fillable = ['estabelecimento_id', 'nome'];

    public function servicos(): BelongsToMany
    {
        return $this->belongsToMany(
            ServicoModel::class,
            'profissional_servico',
            'profissional_id',
            'servico_id'
        );
    }

    public function estabelecimento(): BelongsTo
    {
        return $this->belongsTo(EstabelecimentoModel::class, 'estabelecimento_id');
    }
}
