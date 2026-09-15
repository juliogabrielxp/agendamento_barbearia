import { useEffect, useState } from 'react';
import api from '../lib/api';

export default function AgendaTab() {
    const hoje = new Date().toISOString().split('T')[0];
    const [data, setData] = useState(hoje);
    const [agendamentos, setAgendamentos] = useState([]);
    const [carregando, setCarregando] = useState(true);

    useEffect(() => {
        setCarregando(true);
        api.get(`/api/agenda?data=${data}`)
            .then(response => setAgendamentos(response.data))
            .finally(() => setCarregando(false));
    }, [data]);

    function formatarHora(dataHora) {
        return new Date(dataHora).toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
    }

    return (
        <div>
            <h1 className="text-lg font-semibold text-neutral-900 mb-4">Agenda</h1>

            <div className="bg-white border border-neutral-200 rounded-xl p-4 mb-6">
                <label className="text-xs font-medium text-neutral-500 block mb-2">Ver agenda do dia</label>
                <input type="date" value={data} onChange={e => setData(e.target.value)} className="border border-neutral-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-neutral-900" />
            </div>

            {carregando ? (
                <p className="text-neutral-400 text-sm text-center py-8">Carregando...</p>
            ) : (
                <div className="space-y-2">
                    {agendamentos.map(agendamento => (
                        <div key={agendamento.id} className="bg-white border border-neutral-200 rounded-xl p-4 flex items-center gap-3">
                            <div className="bg-neutral-900 text-white text-xs font-semibold px-2.5 py-1.5 rounded-lg shrink-0">
                                {formatarHora(agendamento.inicio)}
                            </div>
                            <div className="min-w-0">
                                <p className="font-medium text-neutral-900 text-sm truncate">{agendamento.servico?.nome}</p>
                                <p className="text-xs text-neutral-400 truncate">{agendamento.cliente?.nome} · {agendamento.profissional?.nome}</p>
                            </div>
                        </div>
                    ))}
                    {agendamentos.length === 0 && <p className="text-neutral-400 text-sm text-center py-8">Nenhum agendamento para esse dia.</p>}
                </div>
            )}
        </div>
    );
}
