import { useEffect, useState } from 'react';
import { X } from 'lucide-react';
import api from '../lib/api';

export default function MeusAgendamentosTab() {
    const [agendamentos, setAgendamentos] = useState([]);
    const [carregando, setCarregando] = useState(true);
    const [cancelandoId, setCancelandoId] = useState(null);

    function carregarAgendamentos() {
        api.get('/api/meus-agendamentos')
            .then(response => setAgendamentos(response.data))
            .finally(() => setCarregando(false));
    }

    useEffect(() => {
        carregarAgendamentos();
    }, []);

    function handleCancelar(id) {
        if (!confirm('Tem certeza que deseja cancelar esse agendamento?')) {
            return;
        }

        setCancelandoId(id);
        api.delete(`/api/meus-agendamentos/${id}`)
            .then(() => carregarAgendamentos())
            .finally(() => setCancelandoId(null));
    }

    function formatarData(dataHora) {
        return new Date(dataHora).toLocaleDateString('pt-BR', { day: '2-digit', month: 'short' });
    }

    function formatarHora(dataHora) {
        return new Date(dataHora).toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
    }

    if (carregando) {
        return <p className="text-neutral-400 text-sm text-center py-8">Carregando...</p>;
    }

    return (
        <div>
            <h1 className="text-lg font-semibold text-neutral-900 mb-4">Meus Agendamentos</h1>

            <div className="space-y-2">
                {agendamentos.map(agendamento => (
                    <div key={agendamento.id} className="bg-white border border-neutral-200 rounded-xl p-4 flex items-center gap-3">
                        <div className="bg-neutral-900 text-white text-xs font-semibold px-2.5 py-2 rounded-lg text-center shrink-0 min-w-14">
                            <div>{formatarData(agendamento.inicio)}</div>
                            <div className="text-[11px] opacity-80">{formatarHora(agendamento.inicio)}</div>
                        </div>
                        <div className="min-w-0 flex-1">
                            <p className="font-medium text-neutral-900 text-sm truncate">{agendamento.servico?.nome}</p>
                            <p className="text-xs text-neutral-400 truncate">
                                {agendamento.profissional?.barbearia?.nome} · com {agendamento.profissional?.nome}
                            </p>
                        </div>
                        <button
                            onClick={() => handleCancelar(agendamento.id)}
                            disabled={cancelandoId === agendamento.id}
                            className="p-2 text-neutral-400 hover:text-red-600 transition shrink-0 disabled:opacity-50"
                            aria-label="Cancelar agendamento"
                        >
                            <X size={18} />
                        </button>
                    </div>
                ))}
                {agendamentos.length === 0 && (
                    <p className="text-neutral-400 text-sm text-center py-8">Você ainda não tem agendamentos.</p>
                )}
            </div>
        </div>
    );
}
