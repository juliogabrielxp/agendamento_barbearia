import { useEffect, useMemo, useRef, useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { CalendarCheck, Check, Clock, LayoutDashboard, LogIn, MapPin, Phone } from 'lucide-react';
import api from '../lib/api';
import ImagemComFallback from '../components/ImagemComFallback';
import ImagemAmpliavel from '../components/ImagemAmpliavel';
import { formatarDataPorExtenso, formatarDuracao, formatarPreco, proximosDias } from '../lib/formatar';
import { lerPendente, limparPendente, salvarPendente } from '../lib/agendamentoPendente';

const DIAS = proximosDias(21);

export default function Vitrine() {
    const navigate = useNavigate();

    const [estabelecimento, setEstabelecimento] = useState(null);
    const [erroCarregamento, setErroCarregamento] = useState(false);
    // undefined = ainda verificando; null = visitante sem login
    const [conta, setConta] = useState(undefined);

    const [servicoId, setServicoId] = useState(null);
    const [profissionalId, setProfissionalId] = useState(null);
    const [data, setData] = useState(null);
    const [horarios, setHorarios] = useState([]);
    const [carregandoHorarios, setCarregandoHorarios] = useState(false);
    const [recargaDeHorarios, setRecargaDeHorarios] = useState(0);
    const [horarioEscolhido, setHorarioEscolhido] = useState(null);
    const [mensagem, setMensagem] = useState(null);
    const [enviando, setEnviando] = useState(false);

    // Horário que o cliente tinha escolhido antes de fazer login
    const horarioDesejado = useRef(null);
    const resumoRef = useRef(null);

    useEffect(() => {
        api.get('/api/estabelecimento')
            .then(response => setEstabelecimento(response.data))
            .catch(() => setErroCarregamento(true));

        api.get('/api/me')
            .then(response => setConta(response.data))
            .catch(() => setConta(null));
    }, []);

    // Na volta do login, retoma a escolha que ficou guardada
    useEffect(() => {
        if (!estabelecimento) return;

        const pendente = lerPendente();
        limparPendente();
        if (!pendente || !estabelecimento.servicos.some(s => s.id === pendente.servicoId)) return;

        setServicoId(pendente.servicoId);
        setProfissionalId(pendente.profissionalId);
        setData(pendente.data);
        horarioDesejado.current = pendente.horario;
    }, [estabelecimento]);

    const servico = estabelecimento?.servicos.find(s => s.id === servicoId) ?? null;

    const profissionaisDoServico = useMemo(
        () => (estabelecimento && servicoId
            ? estabelecimento.profissionais.filter(p => p.servicos.includes(servicoId))
            : []),
        [estabelecimento, servicoId]
    );

    // Com uma profissional só, ela já fica escolhida e o passo nem aparece
    useEffect(() => {
        if (profissionaisDoServico.length === 1) {
            setProfissionalId(profissionaisDoServico[0].id);
        }
    }, [profissionaisDoServico]);

    useEffect(() => {
        setHorarioEscolhido(null);

        if (!servicoId || !profissionalId || !data) {
            setHorarios([]);
            return undefined;
        }

        let cancelado = false;
        setCarregandoHorarios(true);

        api.get('/api/horarios-disponiveis', {
            params: { servico_id: servicoId, profissional_id: profissionalId, data },
        })
            .then(response => {
                if (cancelado) return;
                setHorarios(response.data);

                const desejado = horarioDesejado.current;
                horarioDesejado.current = null;
                if (!desejado) return;

                if (response.data.includes(desejado)) {
                    setHorarioEscolhido(desejado);
                } else {
                    setMensagem({ tipo: 'erro', texto: 'O horário que você tinha escolhido não está mais livre. Escolha outro.' });
                }
            })
            .catch(() => !cancelado && setHorarios([]))
            .finally(() => !cancelado && setCarregandoHorarios(false));

        return () => { cancelado = true; };
    }, [servicoId, profissionalId, data, recargaDeHorarios]);

    useEffect(() => {
        if (horarioEscolhido) {
            resumoRef.current?.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    }, [horarioEscolhido]);

    function escolherServico(id) {
        if (id === servicoId) return;
        setServicoId(id);
        setProfissionalId(null);
        setMensagem(null);
    }

    function escolherProfissional(id) {
        setProfissionalId(id);
        setMensagem(null);
    }

    function escolherData(valor) {
        setData(valor);
        setMensagem(null);
    }

    function irParaLogin(escolha) {
        salvarPendente(escolha);
        navigate('/app/entrar?motivo=agendar');
    }

    async function confirmar() {
        setMensagem(null);
        const escolha = { servicoId, profissionalId, data, horario: horarioEscolhido };

        if (!conta) {
            irParaLogin(escolha);
            return;
        }

        if (!conta.cliente) {
            setMensagem({ tipo: 'erro', texto: 'Você entrou com a conta do estabelecimento. Saia e entre como cliente para agendar.' });
            return;
        }

        setEnviando(true);
        try {
            await api.post('/api/agendamentos', {
                servico_id: servicoId,
                profissional_id: profissionalId,
                inicio: `${data} ${horarioEscolhido}:00`,
            });
            navigate('/app/minha-conta', { state: { feedback: 'Horário confirmado! Te esperamos.' } });
        } catch (error) {
            const status = error.response?.status;

            if (status === 401) {
                irParaLogin(escolha);
                return;
            }

            setMensagem({
                tipo: 'erro',
                texto: status === 409
                    ? 'Esse horário acabou de ser ocupado. Escolha outro.'
                    : error.response?.data?.erro ?? 'Não foi possível agendar. Tente novamente.',
            });

            if (status === 409) setRecargaDeHorarios(n => n + 1);
        } finally {
            setEnviando(false);
        }
    }

    if (erroCarregamento) {
        return (
            <div className="min-h-screen flex items-center justify-center bg-neutral-50 px-4">
                <p className="text-neutral-500 text-sm text-center">Não foi possível carregar a página. Tente novamente em instantes.</p>
            </div>
        );
    }

    if (!estabelecimento) {
        return <div className="min-h-screen flex items-center justify-center bg-neutral-50 text-neutral-400 text-sm">Carregando...</div>;
    }

    const precisaEscolherProfissional = profissionaisDoServico.length > 1;
    const etapaDoDia = precisaEscolherProfissional ? 3 : 2;

    return (
        <div className="min-h-screen bg-neutral-50">
            <div className="max-w-xl mx-auto px-4 pb-16">
                <nav className="flex justify-end py-4 min-h-[60px]">
                    <AcessoDaConta conta={conta} />
                </nav>

                <header className="flex flex-col items-center text-center mb-8">
                    <ImagemComFallback src={estabelecimento.imagem_url} nome={estabelecimento.nome} tamanho={88} />
                    <h1 className="text-2xl font-semibold text-neutral-900 mt-4 leading-tight">{estabelecimento.nome}</h1>

                    <div className="mt-3 space-y-1.5 text-sm text-neutral-500">
                        {estabelecimento.endereco && (
                            <p className="flex items-center justify-center gap-1.5">
                                <MapPin size={14} className="shrink-0" />
                                <span>{estabelecimento.endereco}</span>
                            </p>
                        )}
                        {estabelecimento.hora_abertura && (
                            <p className="flex items-center justify-center gap-1.5">
                                <Clock size={14} className="shrink-0" />
                                <span>Atendimento das {estabelecimento.hora_abertura} às {estabelecimento.hora_fechamento}</span>
                            </p>
                        )}
                        {estabelecimento.telefone && (
                            <a href={`tel:${estabelecimento.telefone.replace(/\D/g, '')}`} className="flex items-center justify-center gap-1.5 hover:text-neutral-800">
                                <Phone size={14} className="shrink-0" />
                                <span>{estabelecimento.telefone}</span>
                            </a>
                        )}
                    </div>
                </header>

                <section aria-labelledby="titulo-servicos">
                    <Etapa numero={1} id="titulo-servicos">Escolha o serviço</Etapa>
                    <div className="space-y-2" role="radiogroup" aria-labelledby="titulo-servicos">
                        {estabelecimento.servicos.map(item => (
                            <CartaoDeServico
                                key={item.id}
                                servico={item}
                                selecionado={item.id === servicoId}
                                onEscolher={() => escolherServico(item.id)}
                            />
                        ))}
                        {estabelecimento.servicos.length === 0 && (
                            <p className="text-neutral-400 text-sm text-center py-8">Nenhum serviço disponível para agendamento ainda.</p>
                        )}
                    </div>
                </section>

                {precisaEscolherProfissional && (
                    <section className="mt-8" aria-labelledby="titulo-profissional">
                        <Etapa numero={2} id="titulo-profissional">Com quem?</Etapa>
                        <div className="flex flex-wrap gap-2">
                            {profissionaisDoServico.map(profissional => (
                                <Pilula
                                    key={profissional.id}
                                    ativa={profissional.id === profissionalId}
                                    onClick={() => escolherProfissional(profissional.id)}
                                >
                                    {profissional.nome}
                                </Pilula>
                            ))}
                        </div>
                    </section>
                )}

                {servico && profissionalId && (
                    <section className="mt-8" aria-labelledby="titulo-dia">
                        <Etapa numero={etapaDoDia} id="titulo-dia">Escolha o dia</Etapa>
                        <div className="flex gap-2 overflow-x-auto pb-2 -mx-4 px-4 scroll-px-4 snap-x">
                            {DIAS.map(dia => {
                                const ativo = dia.valor === data;
                                return (
                                    <button
                                        key={dia.valor}
                                        onClick={() => escolherData(dia.valor)}
                                        aria-pressed={ativo}
                                        className={`snap-start shrink-0 w-16 py-2.5 rounded-xl border text-center transition ${
                                            ativo
                                                ? 'bg-neutral-900 border-neutral-900 text-white'
                                                : 'bg-white border-neutral-200 text-neutral-700 hover:border-neutral-400'
                                        }`}
                                    >
                                        <span className={`block text-[11px] capitalize ${ativo ? 'text-neutral-300' : 'text-neutral-400'}`}>{dia.semana}</span>
                                        <span className="block text-lg font-semibold leading-tight">{dia.dia}</span>
                                        <span className={`block text-[11px] ${ativo ? 'text-neutral-300' : 'text-neutral-400'}`}>{dia.mes}</span>
                                    </button>
                                );
                            })}
                        </div>
                    </section>
                )}

                {servico && profissionalId && data && (
                    <section className="mt-8" aria-labelledby="titulo-horario">
                        <Etapa numero={etapaDoDia + 1} id="titulo-horario">Escolha o horário</Etapa>
                        {carregandoHorarios ? (
                            <p className="text-sm text-neutral-400">Carregando horários...</p>
                        ) : horarios.length === 0 ? (
                            <p className="text-sm text-neutral-500 bg-white border border-neutral-200 rounded-xl px-4 py-3">
                                Sem horário livre para {servico.nome.toLowerCase()} nesse dia. Tente outro dia.
                            </p>
                        ) : (
                            <div className="grid grid-cols-4 gap-2">
                                {horarios.map(horario => (
                                    <button
                                        key={horario}
                                        onClick={() => { setHorarioEscolhido(horario); setMensagem(null); }}
                                        aria-pressed={horarioEscolhido === horario}
                                        className={`py-2.5 rounded-lg text-sm font-medium border transition ${
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
                    </section>
                )}

                {mensagem && !horarioEscolhido && (
                    <p className="mt-4 text-sm text-red-600">{mensagem.texto}</p>
                )}

                {horarioEscolhido && servico && (
                    <section ref={resumoRef} className="mt-8 bg-white border border-neutral-200 rounded-xl p-5">
                        <p className="text-xs font-medium text-neutral-500 mb-2">Seu horário</p>
                        <p className="font-semibold text-neutral-900">{servico.nome}</p>
                        <p className="text-sm text-neutral-700 mt-1 first-letter:uppercase">
                            {formatarDataPorExtenso(data)}, às {horarioEscolhido}
                        </p>
                        <p className="text-xs text-neutral-400 mt-1">
                            {formatarDuracao(servico.duracao_em_minutos)} · {formatarPreco(servico.preco)}
                        </p>

                        <button
                            onClick={confirmar}
                            disabled={enviando || conta === undefined}
                            className="w-full mt-5 bg-neutral-900 text-white py-3 rounded-lg text-sm font-medium hover:bg-neutral-700 transition disabled:opacity-50"
                        >
                            {enviando ? 'Agendando...' : conta ? 'Confirmar agendamento' : 'Entrar para confirmar'}
                        </button>

                        {conta === null && (
                            <p className="text-xs text-neutral-400 text-center mt-2">
                                Você entra ou cria sua conta e volta direto para cá, com o horário guardado.
                            </p>
                        )}

                        {mensagem && <p className="text-sm text-red-600 mt-3">{mensagem.texto}</p>}
                    </section>
                )}

                <footer className="mt-16 text-center">
                    <Link to="/app/painel/entrar" className="text-xs text-neutral-400 hover:text-neutral-700 transition">
                        Área da profissional
                    </Link>
                </footer>
            </div>
        </div>
    );
}

function AcessoDaConta({ conta }) {
    if (conta === undefined) return null;

    const classe = 'flex items-center gap-1.5 text-sm font-medium text-neutral-700 bg-white border border-neutral-200 rounded-full px-3.5 py-1.5 hover:border-neutral-400 transition';

    if (conta?.barbearia) {
        return <Link to="/app/painel" className={classe}><LayoutDashboard size={15} /> Painel</Link>;
    }

    if (conta?.cliente) {
        return <Link to="/app/minha-conta" className={classe}><CalendarCheck size={15} /> Meus horários</Link>;
    }

    return <Link to="/app/entrar" className={classe}><LogIn size={15} /> Entrar</Link>;
}

function Etapa({ numero, id, children }) {
    return (
        <h2 id={id} className="flex items-center gap-2 text-sm font-semibold text-neutral-900 mb-3">
            <span className="w-5 h-5 rounded-full bg-neutral-900 text-white text-[11px] flex items-center justify-center">{numero}</span>
            {children}
        </h2>
    );
}

function Pilula({ ativa, onClick, children }) {
    return (
        <button
            onClick={onClick}
            aria-pressed={ativa}
            className={`px-4 py-2 rounded-full text-sm font-medium border transition ${
                ativa ? 'bg-neutral-900 text-white border-neutral-900' : 'bg-white text-neutral-700 border-neutral-200 hover:border-neutral-400'
            }`}
        >
            {children}
        </button>
    );
}

function CartaoDeServico({ servico, selecionado, onEscolher }) {
    return (
        <div
            role="radio"
            aria-checked={selecionado}
            tabIndex={0}
            onClick={onEscolher}
            onKeyDown={event => {
                if (event.target !== event.currentTarget) return;
                if (event.key === 'Enter' || event.key === ' ') {
                    event.preventDefault();
                    onEscolher();
                }
            }}
            className={`flex items-center gap-3 bg-white border rounded-xl p-3 cursor-pointer transition ${
                selecionado ? 'border-neutral-900 ring-1 ring-neutral-900' : 'border-neutral-200 hover:border-neutral-400'
            }`}
        >
            <ImagemAmpliavel src={servico.imagem_url} nome={servico.nome} tamanho={64} redonda={false} />

            <div className="min-w-0 flex-1">
                <p className="font-medium text-neutral-900 text-sm">{servico.nome}</p>
                <p className="text-xs text-neutral-400 mt-1">
                    {formatarDuracao(servico.duracao_em_minutos)} · {formatarPreco(servico.preco)}
                </p>
            </div>

            {selecionado && <Check size={18} className="text-neutral-900 shrink-0" />}
        </div>
    );
}
