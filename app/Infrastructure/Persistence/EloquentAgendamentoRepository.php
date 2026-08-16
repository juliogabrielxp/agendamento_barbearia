<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\Agendamento;
use App\Domain\Contracts\AgendamentoRepositoryInterface;
use App\Infrastructure\Persistence\Eloquent\AgendamentoModel;
use DateTimeImmutable;

class EloquentAgendamentoRepository implements AgendamentoRepositoryInterface
{
    public function buscarPorProfissionalEData(int $profissionalId, DateTimeImmutable $data): array
    {
        $inicioDoDia = $data->setTime(0, 0);
        $fimDoDia = $data->setTime(23, 59, 59);

        $registros = AgendamentoModel::query()
            ->where('profissional_id', $profissionalId)
            ->whereBetween('inicio', [$inicioDoDia, $fimDoDia])
            ->with('servico') // eager load pra pegar a duração
            ->get();

        return $registros->map(function (AgendamentoModel $registro) {
        return new Agendamento(
                profissionalId: $registro->profissional_id,
                clienteId: $registro->cliente_id,
                servicoId: $registro->servico_id,
                inicio: new DateTimeImmutable($registro->inicio->format('Y-m-d H:i:s')),
                duracaoEmMinutos: $registro->servico->duracao_em_minutos
            );
        })->all();
    }

    public function salvar(Agendamento $agendamento): void
    {
        AgendamentoModel::create([
            'profissional_id' => $agendamento->profissionalId(),
            'cliente_id' => $agendamento->clienteId(),
            'servico_id' => $agendamento->servicoId(),
            'inicio' => $agendamento->inicio(),
        ]);
    }
}
