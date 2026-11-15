import { useEffect, useState } from 'react';
import { X } from 'lucide-react';
import api from '../lib/api';

export default function MeusAgendamentosTab() {
    const [agendamentos, setAgendamentos] = useState([]);
    const [carregando, setCarregando] = useState(true);
    const [cancelandoId, setCancelandoId] = useState(null);
    const [proximoCursor, setProximoCursor] = useState(null);
    const [carregandoMais, setCarregandoMais] = useState(false);

    // A API devolve 20 por vez; next_cursor aponta para os 20 seguintes (mais antigos)
    function carregarAgendamentos() {
        api.get('/api/meus-agendamentos')
            .then(response => {
                setAgendamentos(response.data.data);
                setProximoCursor(response.data.next_cursor);
            })
            .finally(() => setCarregando(false));
    }

    function carregarMais() {
        setCarregandoMais(true);
        api.get('/api/meus-agendamentos', { params: { cursor: proximoCursor } })
            .then(response => {
                setAgendamentos(anteriores => [...anteriores, ...response.data.data]);
                setProximoCursor(response.data.next_cursor);
            })
            .finally(() => setCarregandoMais(false));
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
            .catch(error => {
                alert(error.response?.data?.message ?? 'Não foi possível cancelar o agendamento.');
                carregarAgendamentos();
            })
            .finally(() => setCancelandoId(null));
    }

    function formatarData(dataHora) {
        return new Date(dataHora).toLocaleDateString('pt-BR', { day: '2-digit', month: 'short' });
    }

    function formatarHora(dataHora) {
        return new Date(dataHora).toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
    }

    if (carregando) {
        return <p className="text-texto-2 text-base text-center py-10">Carregando…</p>;
    }

    return (
        <div>
            <h1 className="titulo mb-4">Meus horários</h1>

            <div className="space-y-3">
                {agendamentos.map(agendamento => (
                    <div key={agendamento.id} className="cartao py-3 pl-3 pr-2 flex items-center gap-3">
                        <div className="bg-preenchimento text-texto text-sm font-semibold px-2.5 py-2 rounded-xl text-center shrink-0 min-w-16">
                            <div>{formatarData(agendamento.inicio)}</div>
                            <div className="text-sm font-medium text-texto-2 tabular-nums">{formatarHora(agendamento.inicio)}</div>
                        </div>
                        <div className="min-w-0 flex-1">
                            <p className="font-semibold text-texto text-base truncate">{agendamento.servico?.nome}</p>
                            <p className="text-sm text-texto-2 truncate">
                                com {agendamento.profissional?.nome}
                                {agendamento.status === 'concluido' && ' · concluído'}
                                {agendamento.status === 'cancelado' && ' · cancelado'}
                            </p>
                        </div>
                        {/* Depois que o horário começa, quem fecha o atendimento é a dona (o servidor recusa) */}
                        {agendamento.status === 'confirmado' && new Date(agendamento.inicio) > new Date() && (
                        <button
                            onClick={() => handleCancelar(agendamento.id)}
                            disabled={cancelandoId === agendamento.id}
                            className="btn-icone hover:text-perigo"
                            aria-label="Cancelar agendamento"
                        >
                            <X size={20} />
                        </button>
                        )}
                    </div>
                ))}
                {agendamentos.length === 0 && (
                    <p className="text-texto-2 text-base text-center py-10">Você ainda não tem horários marcados aqui.</p>
                )}
                {proximoCursor && (
                    <button
                        onClick={carregarMais}
                        disabled={carregandoMais}
                        className="btn-texto w-full"
                    >
                        {carregandoMais ? 'Carregando…' : 'Ver mais antigos'}
                    </button>
                )}
            </div>
        </div>
    );
}
