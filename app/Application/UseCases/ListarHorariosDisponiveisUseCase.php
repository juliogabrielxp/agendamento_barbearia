<?php

declare(strict_types=1);

namespace App\Application\UseCases;

use App\Domain\Agendamento;
use App\Domain\Contracts\AgendamentoRepositoryInterface;
use DateTimeImmutable;

final class ListarHorariosDisponiveisUseCase
{
    public function __construct(
        private readonly AgendamentoRepositoryInterface $repositorio
    ) {
    }

    public function executar(
        int $profissionalId,
        int $duracaoEmMinutos,
        DateTimeImmutable $data,
        string $horaAbertura,
        string $horaFechamento,
        ?string $intervaloInicio,
        ?string $intervaloFim
    ): array {
        $agendamentosExistentes = $this->repositorio->buscarPorProfissionalEData($profissionalId, $data);

        $blocoAlmoco = $this->criarBlocoDeAlmoco($profissionalId, $data, $intervaloInicio, $intervaloFim);
        if ($blocoAlmoco !== null) {
            $agendamentosExistentes[] = $blocoAlmoco;
        }

        $candidatos = $this->gerarHorariosCandidatos($profissionalId, $duracaoEmMinutos, $data, $horaAbertura, $horaFechamento);

        return array_values(array_filter($candidatos, function (Agendamento $candidato) use ($agendamentosExistentes) {
            foreach ($agendamentosExistentes as $existente) {
                if ($candidato->conflitaCom($existente)) {
                    return false;
                }
            }
            return true;
        }));
    }

    private function criarBlocoDeAlmoco(
        int $profissionalId,
        DateTimeImmutable $data,
        ?string $intervaloInicio,
        ?string $intervaloFim
    ): ?Agendamento {
        if ($intervaloInicio === null || $intervaloFim === null) {
            return null;
        }

        [$horaI, $minI] = explode(':', $intervaloInicio);
        [$horaF, $minF] = explode(':', $intervaloFim);

        $inicio = $data->setTime((int) $horaI, (int) $minI);
        $fim = $data->setTime((int) $horaF, (int) $minF);
        $duracaoEmMinutos = (int) (($fim->getTimestamp() - $inicio->getTimestamp()) / 60);

        return new Agendamento(
            profissionalId: $profissionalId,
            clienteId: 0,
            servicoId: 0,
            inicio: $inicio,
            duracaoEmMinutos: $duracaoEmMinutos
        );
    }

    private function gerarHorariosCandidatos(
        int $profissionalId,
        int $duracaoEmMinutos,
        DateTimeImmutable $data,
        string $horaAbertura,
        string $horaFechamento
    ): array {
        [$horaA, $minA] = explode(':', $horaAbertura);
        [$horaF, $minF] = explode(':', $horaFechamento);

        $candidatos = [];
        $horario = $data->setTime((int) $horaA, (int) $minA);
        $fechamento = $data->setTime((int) $horaF, (int) $minF);

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
