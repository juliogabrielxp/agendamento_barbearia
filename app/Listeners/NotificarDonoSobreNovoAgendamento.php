<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Domain\Contracts\NotificadorDePushInterface;
use App\Events\AgendamentoCriado;
use App\Infrastructure\Persistence\Eloquent\ClienteModel;
use App\Infrastructure\Persistence\Eloquent\ProfissionalModel;
use App\Infrastructure\Persistence\Eloquent\ServicoModel;
use DateTimeImmutable;
use Throwable;

final class NotificarDonoSobreNovoAgendamento
{
    public function __construct(
        private readonly NotificadorDePushInterface $notificador
    ) {
    }

    public function handle(AgendamentoCriado $evento): void
    {
        // Uma falha de notificação nunca pode derrubar um agendamento já confirmado
        try {
            $profissional = ProfissionalModel::with('barbearia')->find($evento->profissionalId);
            $donoId = $profissional?->barbearia?->user_id;

            if ($donoId === null) {
                return;
            }

            $servico = ServicoModel::find($evento->servicoId);
            $cliente = ClienteModel::find($evento->clienteId);
            $inicio = new DateTimeImmutable($evento->inicio);

            $this->notificador->enviarParaUsuario(
                $donoId,
                'Novo agendamento',
                sprintf(
                    '%s agendou %s com %s, %s às %s.',
                    $cliente?->nome ?? 'Um cliente',
                    $servico?->nome ?? 'um serviço',
                    $profissional->nome,
                    $inicio->format('d/m'),
                    $inicio->format('H:i')
                ),
                '/app/dashboard-barbearia'
            );
        } catch (Throwable $e) {
            report($e);
        }
    }
}
