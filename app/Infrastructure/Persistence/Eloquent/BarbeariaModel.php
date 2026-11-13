<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent;

use App\Infrastructure\Persistence\Eloquent\Concerns\TemImagem;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BarbeariaModel extends Model
{
    use TemImagem;

    protected $appends = ['imagem_url'];

    protected $hidden = ['imagem_path'];

    protected $table = 'barbearias';

    protected $fillable = [
        'user_id', 'nome', 'dominio', 'telefone', 'email', 'endereco',
        'hora_abertura', 'hora_fechamento', 'intervalo_inicio', 'intervalo_fim', 'imagem_path',
    ];

    public function servicos(): HasMany
    {
        return $this->hasMany(ServicoModel::class, 'barbearia_id');
    }

    public function profissionais(): HasMany
    {
        return $this->hasMany(ProfissionalModel::class, 'barbearia_id');
    }
}
