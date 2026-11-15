import { useEffect, useState } from 'react';
import { Trash2, Pencil } from 'lucide-react';
import api from '../lib/api';
import ImagemComFallback from './ImagemComFallback';
import SeletorDeImagem from './SeletorDeImagem';
import { formatarDuracao, formatarPreco } from '../lib/formatar';

export default function ServicosTab() {
    const [servicos, setServicos] = useState([]);
    const [nome, setNome] = useState('');
    const [duracao, setDuracao] = useState('');
    const [preco, setPreco] = useState('');
    const [podeAtravessarIntervalo, setPodeAtravessarIntervalo] = useState(false);
    const [erro, setErro] = useState(null);
    const [servicoEmEdicao, setServicoEmEdicao] = useState(null);
    const [arquivoImagem, setArquivoImagem] = useState(null);
    const [chaveSeletor, setChaveSeletor] = useState(0);

    const editando = servicoEmEdicao !== null;
    const duracaoMudou = editando && Number(duracao) !== servicoEmEdicao.duracao_em_minutos;

    function carregarServicos() {
        api.get('/api/servicos').then(response => setServicos(response.data));
    }

    useEffect(() => { carregarServicos(); }, []);

    function limparFormulario() {
        setNome(''); setDuracao(''); setPreco('');
        setPodeAtravessarIntervalo(false);
        setServicoEmEdicao(null);
        setArquivoImagem(null);
        setChaveSeletor(chave => chave + 1);
        setErro(null);
    }

    function iniciarEdicao(servico) {
        setServicoEmEdicao(servico);
        setNome(servico.nome);
        setDuracao(String(servico.duracao_em_minutos));
        setPreco(String(servico.preco));
        setPodeAtravessarIntervalo(Boolean(servico.pode_atravessar_intervalo));
        setArquivoImagem(null);
        setChaveSeletor(chave => chave + 1);
        setErro(null);
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    async function handleSubmit(event) {
        event.preventDefault();
        setErro(null);

        const dados = {
            nome,
            duracao_em_minutos: Number(duracao),
            preco: Number(preco),
            pode_atravessar_intervalo: podeAtravessarIntervalo,
        };

        let servicoSalvo;
        try {
            const resposta = editando
                ? await api.put(`/api/servicos/${servicoEmEdicao.id}`, dados)
                : await api.post('/api/servicos', dados);
            servicoSalvo = resposta.data;
        } catch {
            setErro('Não foi possível salvar. Confira os dados.');
            return;
        }

        let aviso = null;
        if (arquivoImagem) {
            try {
                const formData = new FormData();
                formData.append('imagem', arquivoImagem);
                await api.post(`/api/servicos/${servicoSalvo.id}/imagem`, formData);
            } catch {
                aviso = 'Serviço salvo, mas não foi possível enviar a imagem. Edite o serviço para tentar de novo.';
            }
        }

        limparFormulario();
        carregarServicos();
        if (aviso) setErro(aviso);
    }

    function handleExcluir(id) {
        api.delete(`/api/servicos/${id}`).then(() => {
            if (servicoEmEdicao?.id === id) limparFormulario();
            carregarServicos();
        }).catch(error => {
            alert(error.response?.data?.message ?? 'Não foi possível excluir.');
        });
    }

    return (
        <div>
            <h1 className="titulo mb-4">Serviços</h1>

            <form onSubmit={handleSubmit} className="cartao p-4 mb-6 space-y-4">
                {editando && (
                    <p className="text-sm font-semibold text-texto">Editando: {servicoEmEdicao.nome}</p>
                )}

                <SeletorDeImagem
                    key={chaveSeletor}
                    imagemAtualUrl={servicoEmEdicao?.imagem_url ?? null}
                    nome={nome}
                    redonda={false}
                    tamanho={64}
                    rotulo={editando ? 'Trocar imagem' : 'Adicionar imagem'}
                    onSelecionar={arquivo => setArquivoImagem(arquivo)}
                />

                <input
                    type="text" placeholder="Nome do serviço" aria-label="Nome do serviço" value={nome}
                    onChange={e => setNome(e.target.value)}
                    className="campo"
                    required
                />
                <div className="grid grid-cols-2 gap-3">
                    <input
                        type="number" inputMode="numeric" placeholder="Duração (min)" aria-label="Duração em minutos" value={duracao}
                        onChange={e => setDuracao(e.target.value)}
                        className="campo"
                        required
                    />
                    <input
                        type="number" inputMode="decimal" step="0.01" placeholder="Preço (R$)" aria-label="Preço em reais" value={preco}
                        onChange={e => setPreco(e.target.value)}
                        className="campo"
                        required
                    />
                </div>

                <label className="flex items-start gap-3 py-1 cursor-pointer">
                    <input
                        type="checkbox"
                        checked={podeAtravessarIntervalo}
                        onChange={e => setPodeAtravessarIntervalo(e.target.checked)}
                        className="mt-0.5 size-5 shrink-0 accent-acento"
                    />
                    <span>
                        <span className="block text-base font-medium text-texto">Pode atravessar o intervalo</span>
                        <span className="block text-sm text-texto-2 mt-0.5">
                            Para serviços longos, como tranças: podem passar por cima do horário de almoço, só não começam dentro dele.
                        </span>
                    </span>
                </label>

                {duracaoMudou && (
                    <p className="text-sm text-aviso bg-aviso/12 rounded-xl px-3.5 py-2.5">
                        Alterar a duração muda o tempo ocupado dos agendamentos futuros deste serviço.
                    </p>
                )}

                <button type="submit" className="btn btn-primario w-full">
                    {editando ? 'Salvar alterações' : 'Adicionar serviço'}
                </button>
                {editando && (
                    <button type="button" onClick={limparFormulario} className="btn-texto w-full -mt-2">
                        Cancelar edição
                    </button>
                )}
                {erro && <p role="alert" className="nota-erro">{erro}</p>}
            </form>

            <div className="space-y-3">
                {servicos.map(servico => (
                    <div
                        key={servico.id}
                        className={`cartao py-2.5 pl-3 pr-1.5 flex justify-between items-center gap-2 ${
                            servicoEmEdicao?.id === servico.id ? 'ring-2 ring-acento' : ''
                        }`}
                    >
                        <div className="flex items-center gap-3 min-w-0">
                            <ImagemComFallback src={servico.imagem_url} nome={servico.nome} tamanho={52} redonda={false} />
                            <div className="min-w-0">
                                <p className="font-semibold text-texto text-base truncate">{servico.nome}</p>
                                <p className="text-sm text-texto-2 mt-0.5">
                                    {formatarDuracao(servico.duracao_em_minutos)} · {formatarPreco(servico.preco)}
                                    {servico.pode_atravessar_intervalo && ' · atravessa o intervalo'}
                                </p>
                            </div>
                        </div>
                        <div className="flex items-center shrink-0">
                            <button onClick={() => iniciarEdicao(servico)} className="btn-icone" aria-label="Editar serviço">
                                <Pencil size={19} />
                            </button>
                            <button onClick={() => handleExcluir(servico.id)} className="btn-icone hover:text-perigo" aria-label="Excluir serviço">
                                <Trash2 size={19} />
                            </button>
                        </div>
                    </div>
                ))}
                {servicos.length === 0 && <p className="text-texto-2 text-base text-center py-10">Nenhum serviço cadastrado ainda.</p>}
            </div>
        </div>
    );
}
