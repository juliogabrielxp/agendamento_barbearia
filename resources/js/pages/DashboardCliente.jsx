import { useEffect, useState } from 'react';
import api from '../lib/api';

export default function DashboardCliente() {
    const [barbearias, setBarbearias] = useState([]);
    const [barbeariaSelecionada, setBarbeariaSelecionada] = useState(null);
    const [servicoId, setServicoId] = useState('');
    const [profissionalId, setProfissionalId] = useState('');
    const [dataHora, setDataHora] = useState('');
    const [mensagem, setMensagem] = useState(null);
    const [enviando, setEnviando] = useState(false);
    const [erroCarregamento, setErroCarregamento] = useState(false);

    useEffect(() => {
        api.get('/api/barbearias')
            .then(response => setBarbearias(response.data))
            .catch(() => setErroCarregamento(true));
    }, []);

    function abrirBarbearia(id) {
        api.get(`/api/barbearias/${id}`).then(response => {
            setBarbeariaSelecionada(response.data);
            setServicoId('');
            setProfissionalId('');
            setDataHora('');
            setMensagem(null);
        });
    }

    function voltarParaLista() {
        setBarbeariaSelecionada(null);
    }

    const servicoEscolhido = barbeariaSelecionada?.servicos.find(s => s.id === Number(servicoId));

    const profissionaisQueFazemOServico = barbeariaSelecionada?.profissionais.filter(profissional =>
        profissional.servicos.some(s => s.id === Number(servicoId))
    ) ?? [];

    function handleAgendar(event) {
        event.preventDefault();
        setEnviando(true);
        setMensagem(null);

        api.post('/api/agendamentos', {
            profissional_id: Number(profissionalId),
            servico_id: Number(servicoId),
            duracao_em_minutos: servicoEscolhido.duracao_em_minutos,
            inicio: dataHora.replace('T', ' ') + ':00',
        })
            .then(() => {
                setMensagem({ tipo: 'sucesso', texto: 'Agendamento confirmado com sucesso!' });
                setServicoId('');
                setProfissionalId('');
                setDataHora('');
            })
            .catch(error => {
                const texto = error.response?.status === 409
                    ? 'Esse horário já está ocupado para esse profissional. Escolha outro horário.'
                    : 'Não foi possível agendar. Confira os dados.';
                setMensagem({ tipo: 'erro', texto });
            })
            .finally(() => setEnviando(false));
    }

    if (erroCarregamento) {
        return (
            <div className="min-h-screen flex flex-col items-center justify-center bg-neutral-50 gap-3 px-4">
                <p className="text-neutral-500 text-sm text-center">Não foi possível carregar as barbearias. Faça login novamente.</p>
                <a href="/app" className="text-sm font-medium text-neutral-900 underline">Voltar para o login</a>
            </div>
        );
    }

    if (!barbeariaSelecionada) {
        return (
            <div className="min-h-screen bg-neutral-50 px-4 py-6">
                <h1 className="text-lg font-semibold text-neutral-900 mb-4 max-w-lg mx-auto">Escolha uma barbearia</h1>
                <div className="max-w-lg mx-auto space-y-2">
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
        <div className="min-h-screen bg-neutral-50 px-4 py-6">
            <div className="max-w-lg mx-auto">
                <button onClick={voltarParaLista} className="text-sm text-neutral-400 mb-4 hover:text-neutral-600 transition">
                    ← Voltar
                </button>

                <div className="bg-white border border-neutral-200 rounded-xl p-5">
                    <h1 className="text-lg font-semibold text-neutral-900 mb-0.5">{barbeariaSelecionada.nome}</h1>
                    <p className="text-xs text-neutral-400 mb-5">{barbeariaSelecionada.endereco}</p>

                    <form onSubmit={handleAgendar} className="space-y-4">
                        <div>
                            <label className="text-xs font-medium text-neutral-500 block mb-1.5">Serviço</label>
                            <select
                                value={servicoId}
                                onChange={e => { setServicoId(e.target.value); setProfissionalId(''); }}
                                className="border border-neutral-200 rounded-lg px-3 py-2.5 w-full text-sm focus:outline-none focus:ring-2 focus:ring-neutral-900"
                                required
                            >
                                <option value="">Selecione...</option>
                                {barbeariaSelecionada.servicos.map(servico => (
                                    <option key={servico.id} value={servico.id}>
                                        {servico.nome} — {servico.duracao_em_minutos} min — R$ {servico.preco}
                                    </option>
                                ))}
                            </select>
                        </div>

                        {servicoId && (
                            <div>
                                <label className="text-xs font-medium text-neutral-500 block mb-1.5">Profissional</label>
                                <select
                                    value={profissionalId}
                                    onChange={e => setProfissionalId(e.target.value)}
                                    className="border border-neutral-200 rounded-lg px-3 py-2.5 w-full text-sm focus:outline-none focus:ring-2 focus:ring-neutral-900"
                                    required
                                >
                                    <option value="">Selecione...</option>
                                    {profissionaisQueFazemOServico.map(profissional => (
                                        <option key={profissional.id} value={profissional.id}>{profissional.nome}</option>
                                    ))}
                                </select>
                                {profissionaisQueFazemOServico.length === 0 && (
                                    <p className="text-xs text-neutral-400 mt-1">Nenhum profissional disponível para esse serviço.</p>
                                )}
                            </div>
                        )}

                        {profissionalId && (
                            <div>
                                <label className="text-xs font-medium text-neutral-500 block mb-1.5">Data e horário</label>
                                <input
                                    type="datetime-local"
                                    value={dataHora}
                                    onChange={e => setDataHora(e.target.value)}
                                    className="border border-neutral-200 rounded-lg px-3 py-2.5 w-full text-sm focus:outline-none focus:ring-2 focus:ring-neutral-900"
                                    required
                                />
                            </div>
                        )}

                        {profissionalId && dataHora && (
                            <button
                                type="submit"
                                disabled={enviando}
                                className="w-full bg-neutral-900 text-white py-3 rounded-lg text-sm font-medium hover:bg-neutral-700 transition disabled:opacity-50"
                            >
                                {enviando ? 'Agendando...' : 'Confirmar Agendamento'}
                            </button>
                        )}

                        {mensagem && (
                            <p className={`text-sm ${mensagem.tipo === 'sucesso' ? 'text-green-600' : 'text-red-600'}`}>
                                {mensagem.texto}
                            </p>
                        )}
                    </form>
                </div>
            </div>
        </div>
    );
}
