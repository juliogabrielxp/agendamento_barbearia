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
        }).catch(error => {
            alert(error.response?.data?.message ?? 'Não foi possível excluir.');
        });
    }

    return (
        <div>
            <h1 className="titulo mb-4">Profissionais</h1>

            <form onSubmit={handleSubmit} className="cartao p-4 mb-6">
                {editando && (
                    <p className="text-sm font-semibold text-texto mb-3">Editando: {profissionalEmEdicao.nome}</p>
                )}
                <input
                    type="text" placeholder="Nome do profissional" aria-label="Nome do profissional" value={nome}
                    onChange={e => setNome(e.target.value)}
                    className="campo mb-4"
                    required
                />
                <p className="rotulo mb-2">Serviços que realiza</p>
                <div className="flex flex-wrap gap-2 mb-4">
                    {servicosDisponiveis.map(servico => (
                        <label key={servico.id} className={`inline-flex items-center min-h-11 px-4 rounded-full text-sm font-medium cursor-pointer select-none transition active:scale-95 has-[:focus-visible]:outline-2 has-[:focus-visible]:outline-offset-2 has-[:focus-visible]:outline-acento ${
                            servicosSelecionados.includes(servico.id) ? 'bg-acento text-sobre-acento' : 'bg-preenchimento text-texto'
                        }`}>
                            <input type="checkbox" checked={servicosSelecionados.includes(servico.id)} onChange={() => alternarServico(servico.id)} className="sr-only" />
                            {servico.nome}
                        </label>
                    ))}
                    {servicosDisponiveis.length === 0 && <p className="text-texto-2 text-sm">Cadastre serviços primeiro.</p>}
                </div>
                <button type="submit" className="btn btn-primario w-full">
                    {editando ? 'Salvar alterações' : 'Adicionar profissional'}
                </button>
                {editando && (
                    <button type="button" onClick={limparFormulario} className="btn-texto w-full mt-1">
                        Cancelar edição
                    </button>
                )}
                {erro && <p role="alert" className="nota-erro mt-2">{erro}</p>}
            </form>

            <div className="space-y-3">
                {profissionais.map(profissional => (
                    <div
                        key={profissional.id}
                        className={`cartao py-2.5 pl-4 pr-1.5 flex justify-between items-center gap-2 ${
                            profissionalEmEdicao?.id === profissional.id ? 'ring-2 ring-acento' : ''
                        }`}
                    >
                        <div className="min-w-0">
                            <p className="font-semibold text-texto text-base truncate">{profissional.nome}</p>
                            <p className="text-sm text-texto-2 mt-0.5 truncate">
                                {profissional.servicos?.map(s => s.nome).join(', ') || 'Nenhum serviço vinculado'}
                            </p>
                        </div>
                        <div className="flex items-center shrink-0">
                            <button onClick={() => iniciarEdicao(profissional)} className="btn-icone" aria-label="Editar profissional">
                                <Pencil size={19} />
                            </button>
                            <button onClick={() => handleExcluir(profissional.id)} className="btn-icone hover:text-perigo" aria-label="Excluir profissional">
                                <Trash2 size={19} />
                            </button>
                        </div>
                    </div>
                ))}
                {profissionais.length === 0 && <p className="text-texto-2 text-base text-center py-10">Nenhum profissional cadastrado ainda.</p>}
            </div>
        </div>
    );
}
