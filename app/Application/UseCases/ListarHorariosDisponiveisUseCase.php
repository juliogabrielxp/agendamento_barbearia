<?php

declare(strict_types=1);

namespace App\Application\UseCases;

use App\Domain\Agendamento;
use App\Domain\Contracts\AgendamentoRepositoryInterface;
use App\Domain\Contracts\RelogioInterface;
use App\Domain\Expediente;

/**
 * Horários em que o serviço cabe na agenda do profissional, seguindo as mesmas
 * regras que o AgendarHorarioUseCase aplica na hora de gravar.
 */
final class ListarHorariosDisponiveisUseCase
{
    /**
     * Os horários oferecidos andam numa grade fixa, independente da duração do serviço.
     * Se andassem pela duração, um serviço de 6 h só ofereceria 2 horários no dia.
     */
    public const PASSO_DA_GRADE_EM_MINUTOS = 30;

    public function __construct(
        private readonly AgendamentoRepositoryInterface $repositorio,
        private readonly RelogioInterface $relogio
    ) {}

    /** @return Agendamento[] candidatos livres, em ordem de horário */
    public function executar(ListarHorariosDisponiveisInput $input): array
    {
        $expediente = $this->repositorio->buscarExpedienteDoProfissional($input->profissionalId);

        if ($expediente === null
            || ! $this->repositorio->profissionalRealizaServico($input->profissionalId, $input->servicoId)) {
            return [];
        }

        $ocupados = $this->repositorio->buscarPorProfissionalEData($input->profissionalId, $input->data);
        $agora = $this->relogio->agora();

        $livres = array_filter(
            $this->gerarCandidatos($input, $expediente),
            fn (Agendamento $candidato) => $candidato->inicio() > $agora
                && ! $expediente->bloqueadoPeloIntervalo(
                    $candidato->inicio(),
                    $candidato->fim(),
                    $input->podeAtravessarIntervalo
                )
                && ! $this->conflitaComAlgum($candidato, $ocupados)
        );

        return array_values($livres);
    }

    /** @return Agendamento[] */
    private function gerarCandidatos(ListarHorariosDisponiveisInput $input, Expediente $expediente): array
    {
        $candidatos = [];
        $inicio = $expediente->aberturaNoDia($input->data);
        $fechamento = $expediente->fechamentoNoDia($input->data);

        while ($inicio->modify("+{$input->duracaoEmMinutos} minutes") <= $fechamento) {
            $candidatos[] = new Agendamento(
                profissionalId: $input->profissionalId,
                clienteId: 0,
                servicoId: $input->servicoId,
                inicio: $inicio,
                duracaoEmMinutos: $input->duracaoEmMinutos
            );
            $inicio = $inicio->modify('+'.self::PASSO_DA_GRADE_EM_MINUTOS.' minutes');
        }

        return $candidatos;
    }

    /** @param Agendamento[] $ocupados */
    private function conflitaComAlgum(Agendamento $candidato, array $ocupados): bool
    {
        foreach ($ocupados as $ocupado) {
            if ($candidato->conflitaCom($ocupado)) {
                return true;
            }
        }

        return false;
    }
}
