import { useEffect, useState } from 'react';
import api from '../lib/api';

export default function AgendarTab() {
    const [barbearias, setBarbearias] = useState([]);
    const [barbeariaSelecionada, setBarbeariaSelecionada] = useState(null);
    const [erroCarregamento, setErroCarregamento] = useState(false);

    const [profissionalId, setProfissionalId] = useState('');
    const [servicoId, setServicoId] = useState('');
    const [data, setData] = useState('');
    const [horarios, setHorarios] = useState([]);
    const [horarioEscolhido, setHorarioEscolhido] = useState('');
    const [carregandoHorarios, setCarregandoHorarios] = useState(false);
    const [mensagem, setMensagem] = useState(null);
    const [enviando, setEnviando] = useState(false);

    useEffect(() => {
        api.get('/api/barbearias')
            .then(response => setBarbearias(response.data))
            .catch(() => setErroCarregamento(true));
    }, []);

    function abrirBarbearia(id) {
        api.get(`/api/barbearias/${id}`).then(response => {
            setBarbeariaSelecionada(response.data);
            resetarSelecao();
        });
    }

    function resetarSelecao() {
        setProfissionalId('');
        setServicoId('');
        setData('');
        setHorarios([]);
        setHorarioEscolhido('');
        setMensagem(null);
    }

    const servicoEscolhido = barbeariaSelecionada?.servicos.find(s => s.id === Number(servicoId));

    const servicosDoProfissional = profissionalId
        ? barbeariaSelecionada?.profissionais
            .find(p => p.id === Number(profissionalId))
            ?.servicos ?? []
        : [];

    useEffect(() => {
        if (!profissionalId || !servicoId || !data) {
            setHorarios([]);
            return;
        }

        setCarregandoHorarios(true);
        setHorarioEscolhido('');

        api.get('/api/horarios-disponiveis', {
            params: {
                profissional_id: profissionalId,
                duracao_em_minutos: servicoEscolhido?.duracao_em_minutos,
                data,
            },
        })
            .then(response => setHorarios(response.data))
            .catch(() => setHorarios([]))
            .finally(() => setCarregandoHorarios(false));
    }, [profissionalId, servicoId, data]);

    function handleAgendar() {
        setEnviando(true);
        setMensagem(null);

        api.post('/api/agendamentos', {
            profissional_id: Number(profissionalId),
            servico_id: Number(servicoId),
            duracao_em_minutos: servicoEscolhido.duracao_em_minutos,
            inicio: `${data} ${horarioEscolhido}:00`,
        })
            .then(() => {
                setMensagem({ tipo: 'sucesso', texto: 'Agendamento confirmado com sucesso!' });
                resetarSelecao();
            })
            .catch(error => {
                const texto = error.response?.status === 409
                    ? 'Esse horário acabou de ser ocupado. Escolha outro.'
                    : 'Não foi possível agendar. Confira os dados.';
                setMensagem({ tipo: 'erro', texto });
            })
            .finally(() => setEnviando(false));
    }

    if (erroCarregamento) {
        return <p className="text-neutral-400 text-sm text-center py-8">Não foi possível carregar as barbearias.</p>;
    }

    if (!barbeariaSelecionada) {
        return (
            <div>
                <h1 className="text-lg font-semibold text-neutral-900 mb-4">Escolha uma barbearia</h1>
                <div className="space-y-2">
                    {barbearias.map(barbearia => (
                        <button
                            key={barbearia.id}
                            onClick={() => abrirBarbearia(barbearia.id)}
                            className="w-full bg-white border border-neutral-200 rounded-xl p-4 text-left hover:border-neutral-400 transition"
                        >
                            <p className="font-medium text-neutral-900 text-sm">{barbearia.nome}</p>
                            <p className="text-xs text-neutral-400 mt-0.5">{barbearia.endereco}</p>
                        </button>
                    ))}
                    {barbearias.length === 0 && (
                        <p className="text-neutral-400 text-sm text-center py-8">Nenhuma barbearia disponível ainda.</p>
                    )}
                </div>
            </div>
        );
    }

    return (
        <div>
            <button onClick={() => setBarbeariaSelecionada(null)} className="text-sm text-neutral-400 mb-4 hover:text-neutral-600 transition">
                ← Voltar
            </button>

            <div className="bg-white border border-neutral-200 rounded-xl p-5">
                <h1 className="text-lg font-semibold text-neutral-900 mb-0.5">{barbeariaSelecionada.nome}</h1>
                <p className="text-xs text-neutral-400 mb-5">{barbeariaSelecionada.endereco}</p>

                <div className="space-y-4">
                    <div>
                        <label className="text-xs font-medium text-neutral-500 block mb-1.5">Profissional</label>
                        <select
                            value={profissionalId}
                            onChange={e => { setProfissionalId(e.target.value); setServicoId(''); setData(''); }}
                            className="border border-neutral-200 rounded-lg px-3 py-2.5 w-full text-sm focus:outline-none focus:ring-2 focus:ring-neutral-900"
                        >
                            <option value="">Selecione...</option>
                            {barbeariaSelecionada.profissionais.map(p => (
                                <option key={p.id} value={p.id}>{p.nome}</option>
                            ))}
                        </select>
                    </div>

                    {profissionalId && (
                        <div>
                            <label className="text-xs font-medium text-neutral-500 block mb-1.5">Serviço</label>
                            <select
                                value={servicoId}
                                onChange={e => { setServicoId(e.target.value); setData(''); }}
                                className="border border-neutral-200 rounded-lg px-3 py-2.5 w-full text-sm focus:outline-none focus:ring-2 focus:ring-neutral-900"
                            >
                                <option value="">Selecione...</option>
                                {servicosDoProfissional.map(s => (
                                    <option key={s.id} value={s.id}>{s.nome} — {s.duracao_em_minutos} min — R$ {s.preco}</option>
                                ))}
                            </select>
                        </div>
                    )}

                    {servicoId && (
                        <div>
                            <label className="text-xs font-medium text-neutral-500 block mb-1.5">Data</label>
                            <input
                                type="date"
                                value={data}
                                min={new Date().toISOString().split('T')[0]}
                                onChange={e => setData(e.target.value)}
                                className="border border-neutral-200 rounded-lg px-3 py-2.5 w-full text-sm focus:outline-none focus:ring-2 focus:ring-neutral-900"
                            />
                        </div>
                    )}

                    {data && (
                        <div>
                            <label className="text-xs font-medium text-neutral-500 block mb-2">Horários disponíveis</label>
                            {carregandoHorarios ? (
                                <p className="text-sm text-neutral-400">Carregando horários...</p>
                            ) : horarios.length === 0 ? (
                                <p className="text-sm text-neutral-400">Nenhum horário disponível nesse dia.</p>
                            ) : (
                                <div className="grid grid-cols-4 gap-2">
                                    {horarios.map(horario => (
                                        <button
                                            key={horario}
                                            onClick={() => setHorarioEscolhido(horario)}
                                            className={`py-2 rounded-lg text-sm font-medium border transition ${
                                                horarioEscolhido === horario
                                                    ? 'bg-neutral-900 text-white border-neutral-900'
                                                    : 'bg-white text-neutral-700 border-neutral-200 hover:border-neutral-400'
                                            }`}
                                        >
                                            {horario}
                                        </button>
                                    ))}
                                </div>
                            )}
                        </div>
                    )}

                    {horarioEscolhido && (
                        <button
                            onClick={handleAgendar}
                            disabled={enviando}
                            className="w-full bg-neutral-900 text-white py-3 rounded-lg text-sm font-medium hover:bg-neutral-700 transition disabled:opacity-50"
                        >
                            {enviando ? 'Agendando...' : `Confirmar às ${horarioEscolhido}`}
                        </button>
                    )}

                    {mensagem && (
                        <p className={`text-sm ${mensagem.tipo === 'sucesso' ? 'text-green-600' : 'text-red-600'}`}>
                            {mensagem.texto}
                        </p>
                    )}
                </div>
            </div>
        </div>
    );
}
