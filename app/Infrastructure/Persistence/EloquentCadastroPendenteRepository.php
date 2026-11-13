<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\CadastroPendente;
use App\Domain\Contracts\CadastroPendenteRepositoryInterface;
use App\Infrastructure\Persistence\Eloquent\CadastroPendenteModel;
use DateTimeImmutable;

final class EloquentCadastroPendenteRepository implements CadastroPendenteRepositoryInterface
{
    public function buscarPorEmail(string $email): ?CadastroPendente
    {
        $model = CadastroPendenteModel::where('email', $email)->first();

        return $model ? $this->paraEntidade($model) : null;
    }

    public function travarPorEmail(string $email): ?CadastroPendente
    {
        $model = CadastroPendenteModel::where('email', $email)->lockForUpdate()->first();

        return $model ? $this->paraEntidade($model) : null;
    }

    public function salvar(CadastroPendente $cadastro): void
    {
        CadastroPendenteModel::updateOrCreate(
            ['email' => $cadastro->email],
            [
                'nome' => $cadastro->nome,
                'senha_hash' => $cadastro->senhaHash,
                'codigo_hash' => $cadastro->codigoHash,
                'chave_hash' => $cadastro->chaveHash,
                'tentativas' => $cadastro->tentativas,
                'expira_em' => $cadastro->expiraEm,
                'enviado_em' => $cadastro->enviadoEm,
            ]
        );
    }

    public function registrarTentativaErrada(string $email): void
    {
        CadastroPendenteModel::where('email', $email)->increment('tentativas');
    }

    public function remover(string $email): void
    {
        CadastroPendenteModel::where('email', $email)->delete();
    }

    private function paraEntidade(CadastroPendenteModel $model): CadastroPendente
    {
        return new CadastroPendente(
            nome: $model->nome,
            email: $model->email,
            senhaHash: $model->senha_hash,
            codigoHash: $model->codigo_hash,
            chaveHash: $model->chave_hash,
            tentativas: $model->tentativas,
            expiraEm: DateTimeImmutable::createFromInterface($model->expira_em),
            enviadoEm: DateTimeImmutable::createFromInterface($model->enviado_em),
        );
    }
}
