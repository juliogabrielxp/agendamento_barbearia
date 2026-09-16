<?php

declare(strict_types=1);

namespace App\Application\UseCases;

use App\Domain\Agendamento;
use App\Domain\Contracts\AgendamentoRepositoryInterface;
use DateTimeImmutable;

final class ListarHorariosDisponiveisUseCase
{
    private const HORA_ABERTURA = 8;
    private const HORA_FECHAMENTO = 19;

    public function __construct(
        private readonly AgendamentoRepositoryInterface $repositorio
    ) {
    }

    public function executar(int $profissionalId, int $duracaoEmMinutos, DateTimeImmutable $data): array
    {
        $agendamentosExistentes = $this->repositorio->buscarPorProfissionalEData($profissionalId, $data);

        $candidatos = $this->gerarHorariosCandidatos($profissionalId, $duracaoEmMinutos, $data);

        return array_values(array_filter($candidatos, function (Agendamento $candidato) use ($agendamentosExistentes) {
            foreach ($agendamentosExistentes as $existente) {
                if ($candidato->conflitaCom($existente)) {
                    return false;
                }
            }
            return true;
        }));
    }

    private function gerarHorariosCandidatos(int $profissionalId, int $duracaoEmMinutos, DateTimeImmutable $data): array
    {
        $candidatos = [];
        $horario = $data->setTime(self::HORA_ABERTURA, 0);
        $fechamento = $data->setTime(self::HORA_FECHAMENTO, 0);

        while ($horario->modify("+{$duracaoEmMinutos} minutes") <= $fechamento) {
            $candidatos[] = new Agendamento(
                profissionalId: $profissionalId,
                clienteId: 0,
                servicoId: 0,
                inicio: $horario,
                duracaoEmMinutos: $duracaoEmMinutos
            );
            $horario = $horario->modify("+{$duracaoEmMinutos} minutes");
        }

        return $candidatos;
    }
}
