import { useEffect, useState } from 'react';
import { Trash2 } from 'lucide-react';
import api from '../lib/api';

export default function ServicosTab() {
    const [servicos, setServicos] = useState([]);
    const [nome, setNome] = useState('');
    const [duracao, setDuracao] = useState('');
    const [preco, setPreco] = useState('');
    const [erro, setErro] = useState(null);

    function carregarServicos() {
        api.get('/api/servicos').then(response => setServicos(response.data));
    }

    useEffect(() => { carregarServicos(); }, []);

    function handleSubmit(event) {
        event.preventDefault();
        setErro(null);
        api.post('/api/servicos', {
            nome,
            duracao_em_minutos: Number(duracao),
            preco: Number(preco),
        })
            .then(() => {
                setNome(''); setDuracao(''); setPreco('');
                carregarServicos();
            })
            .catch(() => setErro('Não foi possível salvar. Confira os dados.'));
    }

    function handleExcluir(id) {
        api.delete(`/api/servicos/${id}`).then(() => carregarServicos());
    }

    return (
        <div>
            <h1 className="text-lg font-semibold text-neutral-900 mb-4">Serviços</h1>

            <form onSubmit={handleSubmit} className="bg-white border border-neutral-200 rounded-xl p-4 mb-6 space-y-3">
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
                <button type="submit" className="w-full bg-neutral-900 text-white py-3 rounded-lg text-sm font-medium hover:bg-neutral-700 transition">
                    Adicionar Serviço
                </button>
                {erro && <p className="text-red-600 text-sm">{erro}</p>}
            </form>

            <div className="space-y-2">
                {servicos.map(servico => (
                    <div key={servico.id} className="bg-white border border-neutral-200 rounded-xl p-4 flex justify-between items-center">
                        <div className="min-w-0">
                            <p className="font-medium text-neutral-900 text-sm truncate">{servico.nome}</p>
                            <p className="text-xs text-neutral-400 mt-0.5">{servico.duracao_em_minutos} min · R$ {servico.preco}</p>
                        </div>
                        <button onClick={() => handleExcluir(servico.id)} className="p-2 text-neutral-400 hover:text-red-600 transition shrink-0" aria-label="Excluir serviço">
                            <Trash2 size={18} />
                        </button>
                    </div>
                ))}
                {servicos.length === 0 && <p className="text-neutral-400 text-sm text-center py-8">Nenhum serviço cadastrado ainda.</p>}
            </div>
        </div>
    );
}
