import { useEffect, useState } from 'react';
import { Trash2, Pencil } from 'lucide-react';
import api from '../lib/api';

export default function ProfissionaisTab() {
    const [profissionais, setProfissionais] = useState([]);
    const [servicosDisponiveis, setServicosDisponiveis] = useState([]);
    const [nome, setNome] = useState('');
    const [servicosSelecionados, setServicosSelecionados] = useState([]);
    const [erro, setErro] = useState(null);
    const [profissionalEmEdicao, setProfissionalEmEdicao] = useState(null);

    const editando = profissionalEmEdicao !== null;

    function carregarDados() {
        api.get('/api/profissionais').then(response => setProfissionais(response.data));
        api.get('/api/servicos').then(response => setServicosDisponiveis(response.data));
    }

    useEffect(() => { carregarDados(); }, []);

    function alternarServico(id) {
        setServicosSelecionados(atual =>
            atual.includes(id) ? atual.filter(sid => sid !== id) : [...atual, id]
        );
    }

    function limparFormulario() {
        setNome('');
        setServicosSelecionados([]);
        setProfissionalEmEdicao(null);
        setErro(null);
    }

    function iniciarEdicao(profissional) {
        setProfissionalEmEdicao(profissional);
        setNome(profissional.nome);
        setServicosSelecionados((profissional.servicos ?? []).map(s => s.id));
        setErro(null);
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    function handleSubmit(event) {
        event.preventDefault();
        setErro(null);

        const dados = { nome, servicos: servicosSelecionados };

        const requisicao = editando
            ? api.put(`/api/profissionais/${profissionalEmEdicao.id}`, dados)
            : api.post('/api/profissionais', dados);

        requisicao
            .then(() => {
                limparFormulario();
                carregarDados();
            })
            .catch(() => setErro('Não foi possível salvar. Confira os dados.'));
    }

    function handleExcluir(id) {
        api.delete(`/api/profissionais/${id}`).then(() => {
            if (profissionalEmEdicao?.id === id) limparFormulario();
            carregarDados();
        });
    }

    return (
        <div>
            <h1 className="text-lg font-semibold text-neutral-900 mb-4">Profissionais</h1>

            <form onSubmit={handleSubmit} className="bg-white border border-neutral-200 rounded-xl p-4 mb-6">
                {editando && (
                    <p className="text-xs font-medium text-neutral-500 mb-3">Editando: {profissionalEmEdicao.nome}</p>
                )}
                <input
                    type="text" placeholder="Nome do profissional" value={nome}
                    onChange={e => setNome(e.target.value)}
                    className="w-full border border-neutral-200 rounded-lg px-3 py-2.5 text-sm placeholder:text-neutral-400 focus:outline-none focus:ring-2 focus:ring-neutral-900 mb-3"
                    required
                />
                <p className="text-xs font-medium text-neutral-500 mb-2">Serviços que realiza</p>
                <div className="flex flex-wrap gap-2 mb-4">
                    {servicosDisponiveis.map(servico => (
                        <label key={servico.id} className={`flex items-center gap-1.5 text-xs font-medium px-3 py-2 rounded-full cursor-pointer border transition ${
                            servicosSelecionados.includes(servico.id) ? 'bg-neutral-900 text-white border-neutral-900' : 'bg-white text-neutral-600 border-neutral-200'
                        }`}>
                            <input type="checkbox" checked={servicosSelecionados.includes(servico.id)} onChange={() => alternarServico(servico.id)} className="hidden" />
                            {servico.nome}
                        </label>
                    ))}
                    {servicosDisponiveis.length === 0 && <p className="text-neutral-400 text-xs">Cadastre serviços primeiro.</p>}
                </div>
                <button type="submit" className="w-full bg-neutral-900 text-white py-3 rounded-lg text-sm font-medium hover:bg-neutral-700 transition">
                    {editando ? 'Salvar alterações' : 'Adicionar Profissional'}
                </button>
                {editando && (
                    <button type="button" onClick={limparFormulario} className="w-full text-sm text-neutral-500 hover:text-neutral-800 transition mt-2">
                        Cancelar edição
                    </button>
                )}
                {erro && <p className="text-red-600 text-sm mt-2">{erro}</p>}
            </form>

            <div className="space-y-2">
                {profissionais.map(profissional => (
                    <div
                        key={profissional.id}
                        className={`bg-white border rounded-xl p-4 flex justify-between items-center ${
                            profissionalEmEdicao?.id === profissional.id ? 'border-neutral-900' : 'border-neutral-200'
                        }`}
                    >
                        <div className="min-w-0">
                            <p className="font-medium text-neutral-900 text-sm truncate">{profissional.nome}</p>
                            <p className="text-xs text-neutral-400 mt-0.5 truncate">
                                {profissional.servicos?.map(s => s.nome).join(', ') || 'Nenhum serviço vinculado'}
                            </p>
                        </div>
                        <div className="flex items-center shrink-0">
                            <button onClick={() => iniciarEdicao(profissional)} className="p-2 text-neutral-400 hover:text-neutral-900 transition" aria-label="Editar profissional">
                                <Pencil size={18} />
                            </button>
                            <button onClick={() => handleExcluir(profissional.id)} className="p-2 text-neutral-400 hover:text-red-600 transition" aria-label="Excluir profissional">
                                <Trash2 size={18} />
                            </button>
                        </div>
                    </div>
                ))}
                {profissionais.length === 0 && <p className="text-neutral-400 text-sm text-center py-8">Nenhum profissional cadastrado ainda.</p>}
            </div>
        </div>
    );
}
