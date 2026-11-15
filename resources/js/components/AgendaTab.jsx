import { useEffect, useState } from 'react';
import api from '../lib/api';
import { dataLocal } from '../lib/formatar';

const STATUS_CONFIG = {
    confirmado: { label: 'Confirmado', className: 'bg-info/12 text-info' },
    concluido: { label: 'Concluído', className: 'bg-sucesso/12 text-sucesso' },
    cancelado: { label: 'Cancelado', className: 'bg-perigo/12 text-perigo' },
};

export default function AgendaTab() {
    const hoje = dataLocal();
    const [data, setData] = useState(hoje);
    const [agendamentos, setAgendamentos] = useState([]);
    const [carregando, setCarregando] = useState(true);
    const [atualizandoId, setAtualizandoId] = useState(null);

    function carregar() {
        setCarregando(true);
        api.get(`/api/agenda?data=${data}`)
            .then(response => setAgendamentos(response.data))
            .finally(() => setCarregando(false));
    }

    useEffect(carregar, [data]);

    function formatarHora(dataHora) {
        return new Date(dataHora).toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
    }

    async function atualizarStatus(id, status) {
        setAtualizandoId(id);
        try {
            await api.patch(`/api/agenda/${id}/status`, { status });
            carregar();
        } catch (error) {
            console.error('Erro ao atualizar status', error);
        } finally {
            setAtualizandoId(null);
        }
    }

    return (
        <div>
            <h1 className="titulo mb-4">Agenda</h1>

            <div className="cartao p-4 mb-6">
                <label htmlFor="agenda-data" className="rotulo">Ver agenda do dia</label>
                <input id="agenda-data" type="date" value={data} onChange={e => setData(e.target.value)} className="campo w-auto" />
            </div>

            {carregando ? (
                <p className="text-texto-2 text-base text-center py-10">Carregando…</p>
            ) : (
                <div className="space-y-3">
                    {agendamentos.map(agendamento => {
                        const status = STATUS_CONFIG[agendamento.status] ?? STATUS_CONFIG.confirmado;
                        const podeAgir = agendamento.status === 'confirmado';

                        return (
                            <div key={agendamento.id} className="cartao p-4">
                                <div className="flex items-center gap-3">
                                    <div className="bg-preenchimento text-texto text-base font-semibold tabular-nums px-2.5 py-1.5 rounded-lg shrink-0">
                                        {formatarHora(agendamento.inicio)}
                                    </div>
                                    <div className="min-w-0 flex-1">
                                        <p className="font-semibold text-texto text-base truncate">{agendamento.servico?.nome}</p>
                                        <p className="text-sm text-texto-2 truncate">{agendamento.cliente?.nome} · {agendamento.profissional?.nome}</p>
                                    </div>
                                    <span className={`text-xs font-semibold px-2.5 py-1 rounded-full shrink-0 ${status.className}`}>
                                        {status.label}
                                    </span>
                                </div>

                                {podeAgir && (
                                    <div className="flex gap-2 mt-4 pt-4 border-t border-separador">
                                        <button
                                            onClick={() => atualizarStatus(agendamento.id, 'concluido')}
                                            disabled={atualizandoId === agendamento.id}
                                            className="btn btn-primario flex-1"
                                        >
                                            Concluir
                                        </button>
                                        <button
                                            onClick={() => atualizarStatus(agendamento.id, 'cancelado')}
                                            disabled={atualizandoId === agendamento.id}
                                            className="btn btn-secundario flex-1 text-perigo"
                                        >
                                            Cancelar
                                        </button>
                                    </div>
                                )}
                            </div>
                        );
                    })}
                    {agendamentos.length === 0 && <p className="text-texto-2 text-base text-center py-10">Nenhum agendamento para esse dia.</p>}
                </div>
            )}
        </div>
    );
}
