<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent;

use App\Domain\Expediente;
use App\Infrastructure\Persistence\Eloquent\Concerns\TemImagem;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EstabelecimentoModel extends Model
{
    use TemImagem;

    protected $appends = ['imagem_url'];

    protected $hidden = ['imagem_path'];

    protected $table = 'estabelecimentos';

    protected $fillable = [
        'user_id', 'nome', 'dominio', 'telefone', 'email', 'endereco',
        'hora_abertura', 'hora_fechamento', 'intervalo_inicio', 'intervalo_fim', 'imagem_path',
    ];

    public function servicos(): HasMany
    {
        return $this->hasMany(ServicoModel::class, 'estabelecimento_id');
    }

    public function profissionais(): HasMany
    {
        return $this->hasMany(ProfissionalModel::class, 'estabelecimento_id');
    }

    /** Horário de funcionamento como objeto de domínio ("08:00:00" do banco vira "08:00") */
    public function expediente(): Expediente
    {
        $horario = fn (?string $valor) => $valor ? substr($valor, 0, 5) : null;

        return new Expediente(
            abertura: $horario($this->hora_abertura),
            fechamento: $horario($this->hora_fechamento),
            intervaloInicio: $horario($this->intervalo_inicio),
            intervaloFim: $horario($this->intervalo_fim),
        );
    }
}
