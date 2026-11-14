<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\Agendamento;
use App\Domain\Contracts\AgendamentoRepositoryInterface;
use App\Domain\Expediente;
use App\Infrastructure\Persistence\Eloquent\AgendamentoModel;
use App\Infrastructure\Persistence\Eloquent\ProfissionalModel;
use DateTimeImmutable;
use Illuminate\Support\Facades\DB;

class EloquentAgendamentoRepository implements AgendamentoRepositoryInterface
{
    public function travarAgendaDoProfissional(int $profissionalId): void
    {
        // SELECT ... FOR UPDATE: só tem efeito dentro de uma transação
        ProfissionalModel::query()->whereKey($profissionalId)->lockForUpdate()->first();
    }

    public function buscarExpedienteDoProfissional(int $profissionalId): ?Expediente
    {
        $barbearia = ProfissionalModel::with('barbearia')->find($profissionalId)?->barbearia;

        return $barbearia?->expediente();
    }

    public function profissionalRealizaServico(int $profissionalId, int $servicoId): bool
    {
        return DB::table('profissional_servico')
            ->where('profissional_id', $profissionalId)
            ->where('servico_id', $servicoId)
            ->exists();
    }

    public function buscarPorProfissionalEData(int $profissionalId, DateTimeImmutable $data): array
    {
        $inicioDoDia = $data->setTime(0, 0);
        $fimDoDia = $data->setTime(23, 59, 59);

        $registros = AgendamentoModel::query()
            ->where('profissional_id', $profissionalId)
            ->where('status', '!=', 'cancelado') // cancelado libera o horário
            ->whereBetween('inicio', [$inicioDoDia, $fimDoDia])
            ->get();

        return $registros->map(function (AgendamentoModel $registro) {
            return new Agendamento(
                profissionalId: $registro->profissional_id,
                clienteId: $registro->cliente_id,
                servicoId: $registro->servico_id,
                inicio: new DateTimeImmutable($registro->inicio->format('Y-m-d H:i:s')),
                // A duração reservada, não a do cadastro atual do serviço (que a dona pode mudar)
                duracaoEmMinutos: $registro->duracao_em_minutos
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
            'duracao_em_minutos' => $agendamento->duracaoEmMinutos(),
            'preco' => $agendamento->preco(),
        ]);
    }

    public function cancelar(int $agendamentoId, int $clienteId): void
    {
        // Não apaga: fica no histórico como cancelado, igual ao cancelamento pela dona
        AgendamentoModel::where('id', $agendamentoId)
            ->where('cliente_id', $clienteId)
            ->update(['status' => 'cancelado']);
    }

    public function buscarPorId(int $agendamentoId): ?Agendamento
    {
        return $this->paraDominio(AgendamentoModel::find($agendamentoId));
    }

    public function buscarPorIdTravando(int $agendamentoId): ?Agendamento
    {
        // SELECT ... FOR UPDATE: só tem efeito dentro de uma transação
        return $this->paraDominio(AgendamentoModel::lockForUpdate()->find($agendamentoId));
    }

    private function paraDominio(?AgendamentoModel $registro): ?Agendamento
    {
        if (! $registro) {
            return null;
        }

        return new Agendamento(
            profissionalId: $registro->profissional_id,
            clienteId: $registro->cliente_id,
            servicoId: $registro->servico_id,
            inicio: new DateTimeImmutable($registro->inicio->format('Y-m-d H:i:s')),
            duracaoEmMinutos: $registro->duracao_em_minutos,
            id: $registro->id,
            status: $registro->status,
            preco: $registro->preco,
        );
    }

    public function atualizarStatus(int $agendamentoId, string $status): void
    {
        AgendamentoModel::where('id', $agendamentoId)->update(['status' => $status]);
    }
}
