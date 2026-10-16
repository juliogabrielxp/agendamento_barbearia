import { useEffect, useState } from 'react';
import { Trash2, Pencil } from 'lucide-react';
import api from '../lib/api';

export default function ServicosTab() {
    const [servicos, setServicos] = useState([]);
    const [nome, setNome] = useState('');
    const [duracao, setDuracao] = useState('');
    const [preco, setPreco] = useState('');
    const [erro, setErro] = useState(null);
    const [servicoEmEdicao, setServicoEmEdicao] = useState(null);

    const editando = servicoEmEdicao !== null;
    const duracaoMudou = editando && Number(duracao) !== servicoEmEdicao.duracao_em_minutos;

    function carregarServicos() {
        api.get('/api/servicos').then(response => setServicos(response.data));
    }

    useEffect(() => { carregarServicos(); }, []);

    function limparFormulario() {
        setNome(''); setDuracao(''); setPreco('');
        setServicoEmEdicao(null);
        setErro(null);
    }

    function iniciarEdicao(servico) {
        setServicoEmEdicao(servico);
        setNome(servico.nome);
        setDuracao(String(servico.duracao_em_minutos));
        setPreco(String(servico.preco));
        setErro(null);
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    function handleSubmit(event) {
        event.preventDefault();
        setErro(null);

        const dados = {
            nome,
            duracao_em_minutos: Number(duracao),
            preco: Number(preco),
        };

        const requisicao = editando
            ? api.put(`/api/servicos/${servicoEmEdicao.id}`, dados)
            : api.post('/api/servicos', dados);

        requisicao
            .then(() => {
                limparFormulario();
                carregarServicos();
            })
            .catch(() => setErro('Não foi possível salvar. Confira os dados.'));
    }

    function handleExcluir(id) {
        api.delete(`/api/servicos/${id}`).then(() => {
            if (servicoEmEdicao?.id === id) limparFormulario();
            carregarServicos();
        });
    }

    return (
        <div>
            <h1 className="text-lg font-semibold text-neutral-900 mb-4">Serviços</h1>

            <form onSubmit={handleSubmit} className="bg-white border border-neutral-200 rounded-xl p-4 mb-6 space-y-3">
                {editando && (
                    <p className="text-xs font-medium text-neutral-500">Editando: {servicoEmEdicao.nome}</p>
                )}
                <input
                    type="text" placeholder="Nome do serviço" value={nome}
                    onChange={e => setNome(e.target.value)}
                    className="w-full border border-neutral-200 rounded-lg px-3 py-2.5 text-sm placeholder:text-neutral-400 focus:outline-none focus:ring-2 focus:ring-neutral-900"
                    required
                />
                <div className="grid grid-cols-2 gap-3">
                    <input
                        type="number" placeholder="Duração (min)" value={duracao}
                        onChange={e => setDuracao(e.target.value)}
                        className="border border-neutral-200 rounded-lg px-3 py-2.5 text-sm placeholder:text-neutral-400 focus:outline-none focus:ring-2 focus:ring-neutral-900"
                        required
                    />
                    <input
                        type="number" step="0.01" placeholder="Preço (R$)" value={preco}
                        onChange={e => setPreco(e.target.value)}
                        className="border border-neutral-200 rounded-lg px-3 py-2.5 text-sm placeholder:text-neutral-400 focus:outline-none focus:ring-2 focus:ring-neutral-900"
                        required
                    />
                </div>

                {duracaoMudou && (
                    <p className="text-xs text-amber-700 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2">
                        Alterar a duração muda o tempo ocupado dos agendamentos futuros deste serviço.
                    </p>
                )}

                <button type="submit" className="w-full bg-neutral-900 text-white py-3 rounded-lg text-sm font-medium hover:bg-neutral-700 transition">
                    {editando ? 'Salvar alterações' : 'Adicionar Serviço'}
                </button>
                {editando && (
                    <button type="button" onClick={limparFormulario} className="w-full text-sm text-neutral-500 hover:text-neutral-800 transition">
                        Cancelar edição
                    </button>
                )}
                {erro && <p className="text-red-600 text-sm">{erro}</p>}
            </form>

            <div className="space-y-2">
                {servicos.map(servico => (
                    <div
                        key={servico.id}
                        className={`bg-white border rounded-xl p-4 flex justify-between items-center ${
                            servicoEmEdicao?.id === servico.id ? 'border-neutral-900' : 'border-neutral-200'
                        }`}
                    >
                        <div className="min-w-0">
                            <p className="font-medium text-neutral-900 text-sm truncate">{servico.nome}</p>
                            <p className="text-xs text-neutral-400 mt-0.5">{servico.duracao_em_minutos} min · R$ {servico.preco}</p>
                        </div>
                        <div className="flex items-center shrink-0">
                            <button onClick={() => iniciarEdicao(servico)} className="p-2 text-neutral-400 hover:text-neutral-900 transition" aria-label="Editar serviço">
                                <Pencil size={18} />
                            </button>
                            <button onClick={() => handleExcluir(servico.id)} className="p-2 text-neutral-400 hover:text-red-600 transition" aria-label="Excluir serviço">
                                <Trash2 size={18} />
                            </button>
                        </div>
                    </div>
                ))}
                {servicos.length === 0 && <p className="text-neutral-400 text-sm text-center py-8">Nenhum serviço cadastrado ainda.</p>}
            </div>
        </div>
    );
}
