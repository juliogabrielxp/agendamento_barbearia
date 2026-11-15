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
            const semMovimento = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
            resumoRef.current?.scrollIntoView({ behavior: semMovimento ? 'auto' : 'smooth', block: 'center' });
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
            <div className="min-h-dvh flex items-center justify-center px-6">
                <p className="text-texto-2 text-base text-center">Não foi possível carregar a página. Tente novamente em instantes.</p>
            </div>
        );
    }

    if (!estabelecimento) {
        return <div className="min-h-dvh flex items-center justify-center text-texto-2 text-base">Carregando…</div>;
    }

    const precisaEscolherProfissional = profissionaisDoServico.length > 1;
    const etapaDoDia = precisaEscolherProfissional ? 3 : 2;

    return (
        <div className="min-h-dvh">
            <div className="max-w-xl mx-auto px-4 pb-[calc(3rem+env(safe-area-inset-bottom))]">
                <nav className="flex justify-end py-3 min-h-[68px]">
                    <AcessoDaConta conta={conta} />
                </nav>

                <header className="flex flex-col items-center text-center mb-10">
                    <ImagemComFallback src={estabelecimento.imagem_url} nome={estabelecimento.nome} tamanho={96} />
                    <h1 className="titulo-grande mt-4">{estabelecimento.nome}</h1>

                    <div className="mt-3 flex flex-col items-center gap-1.5 text-base text-texto-2">
                        {estabelecimento.endereco && (
                            <p className="flex items-center justify-center gap-1.5">
                                <MapPin size={16} className="shrink-0" />
                                <span>{estabelecimento.endereco}</span>
                            </p>
                        )}
                        {estabelecimento.hora_abertura && (
                            <p className="flex items-center justify-center gap-1.5">
                                <Clock size={16} className="shrink-0" />
                                <span>Atendimento das {estabelecimento.hora_abertura} às {estabelecimento.hora_fechamento}</span>
                            </p>
                        )}
                        {estabelecimento.telefone && (
                            <a href={`tel:${estabelecimento.telefone.replace(/\D/g, '')}`} className="inline-flex items-center justify-center gap-1.5 min-h-11 -my-2 px-2 font-medium text-texto underline-offset-4 hover:underline active:opacity-60">
                                <Phone size={16} className="shrink-0" />
                                <span>{estabelecimento.telefone}</span>
                            </a>
                        )}
                    </div>
                </header>

                <section aria-labelledby="titulo-servicos">
                    <Etapa numero={1} id="titulo-servicos">Escolha o serviço</Etapa>
                    <div className="space-y-3" role="radiogroup" aria-labelledby="titulo-servicos">
                        {estabelecimento.servicos.map(item => (
                            <CartaoDeServico
                                key={item.id}
                                servico={item}
                                selecionado={item.id === servicoId}
                                onEscolher={() => escolherServico(item.id)}
                            />
                        ))}
                        {estabelecimento.servicos.length === 0 && (
                            <p className="text-texto-2 text-base text-center py-10">Nenhum serviço disponível para agendamento ainda.</p>
                        )}
                    </div>
                </section>

                {precisaEscolherProfissional && (
                    <section className="surgir mt-10" aria-labelledby="titulo-profissional">
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
                    <section className="surgir mt-10" aria-labelledby="titulo-dia">
                        <Etapa numero={etapaDoDia} id="titulo-dia">Escolha o dia</Etapa>
                        <div className="flex gap-2 overflow-x-auto py-1 -my-1 pb-3 -mx-4 px-4 scroll-px-4 snap-x [scrollbar-width:none]">
                            {DIAS.map(dia => {
                                const ativo = dia.valor === data;
                                return (
                                    <button
                                        key={dia.valor}
                                        onClick={() => escolherData(dia.valor)}
                                        aria-pressed={ativo}
                                        className={`snap-start shrink-0 w-[4.25rem] py-2.5 rounded-2xl text-center cursor-pointer transition duration-150 active:scale-95 ${
                                            ativo
                                                ? 'bg-acento text-sobre-acento'
                                                : 'bg-superficie text-texto shadow-cartao'
                                        }`}
                                    >
                                        <span className={`block text-xs font-medium capitalize ${ativo ? 'opacity-75' : 'text-texto-2'}`}>{dia.semana}</span>
                                        <span className="block text-xl font-bold leading-tight tabular-nums">{dia.dia}</span>
                                        <span className={`block text-xs font-medium ${ativo ? 'opacity-75' : 'text-texto-2'}`}>{dia.mes}</span>
                                    </button>
                                );
                            })}
                        </div>
                    </section>
                )}

                {servico && profissionalId && data && (
                    <section className="surgir mt-10" aria-labelledby="titulo-horario">
                        <Etapa numero={etapaDoDia + 1} id="titulo-horario">Escolha o horário</Etapa>
                        {carregandoHorarios ? (
                            <p className="text-base text-texto-2">Carregando horários…</p>
                        ) : horarios.length === 0 ? (
                            <p className="cartao text-base text-texto-2 px-4 py-3.5">
                                Sem horário livre para {servico.nome.toLowerCase()} nesse dia. Tente outro dia.
                            </p>
                        ) : (
                            <div className="grid grid-cols-4 gap-2">
                                {horarios.map(horario => (
                                    <button
                                        key={horario}
                                        onClick={() => { setHorarioEscolhido(horario); setMensagem(null); }}
                                        aria-pressed={horarioEscolhido === horario}
                                        className={`min-h-11 rounded-xl text-base font-semibold tabular-nums cursor-pointer transition duration-150 active:scale-95 ${
                                            horarioEscolhido === horario
                                                ? 'bg-acento text-sobre-acento'
                                                : 'bg-superficie text-texto shadow-cartao'
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
                    <p role="alert" className="nota-erro mt-4">{mensagem.texto}</p>
                )}

                {horarioEscolhido && servico && (
                    <section ref={resumoRef} className="surgir cartao mt-10 p-5">
                        <p className="text-sm font-medium text-texto-2 mb-1.5">Seu horário</p>
                        <p className="titulo">{servico.nome}</p>
                        <p className="text-base text-texto mt-1.5 first-letter:uppercase">
                            {formatarDataPorExtenso(data)}, às {horarioEscolhido}
                        </p>
                        <p className="text-sm text-texto-2 mt-1">
                            {formatarDuracao(servico.duracao_em_minutos)} · {formatarPreco(servico.preco)}
                        </p>

                        <button
                            onClick={confirmar}
                            disabled={enviando || conta === undefined}
                            className="btn btn-primario w-full min-h-[3.125rem] mt-5"
                        >
                            {enviando ? 'Agendando…' : conta ? 'Confirmar agendamento' : 'Entrar para confirmar'}
                        </button>

                        {conta === null && (
                            <p className="text-sm text-texto-2 text-center mt-3">
                                Você entra ou cria sua conta e volta direto para cá, com o horário guardado.
                            </p>
                        )}

                        {mensagem && <p role="alert" className="nota-erro mt-3">{mensagem.texto}</p>}
                    </section>
                )}

                <footer className="mt-14 text-center">
                    <Link to="/app/painel/entrar" className="btn-texto text-sm">
                        Área da profissional
                    </Link>
                </footer>
            </div>
        </div>
    );
}

function AcessoDaConta({ conta }) {
    if (conta === undefined) return null;

    const classe = 'btn btn-superficie rounded-full px-4 text-sm';

    if (conta?.estabelecimento) {
        return <Link to="/app/painel" className={classe}><LayoutDashboard size={17} /> Painel</Link>;
    }

    if (conta?.cliente) {
        return <Link to="/app/minha-conta" className={classe}><CalendarCheck size={17} /> Meus horários</Link>;
    }

    return <Link to="/app/entrar" className={classe}><LogIn size={17} /> Entrar</Link>;
}

function Etapa({ numero, id, children }) {
    return (
        <h2 id={id} className="flex items-center gap-2.5 text-lg font-semibold tracking-[-0.01em] text-texto mb-3.5">
            <span className="size-6 rounded-full bg-acento text-sobre-acento text-sm font-bold tabular-nums flex items-center justify-center">{numero}</span>
            {children}
        </h2>
    );
}

function Pilula({ ativa, onClick, children }) {
    return (
        <button
            onClick={onClick}
            aria-pressed={ativa}
            className={`min-h-11 px-5 rounded-full text-base font-medium cursor-pointer transition duration-150 active:scale-95 ${
                ativa ? 'bg-acento text-sobre-acento' : 'bg-superficie text-texto shadow-cartao'
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
            className={`cartao flex items-center gap-3.5 p-3 pr-4 cursor-pointer transition duration-150 active:scale-[0.985] ${
                selecionado ? 'ring-2 ring-acento' : ''
            }`}
        >
            <ImagemAmpliavel src={servico.imagem_url} nome={servico.nome} tamanho={68} redonda={false} />

            <div className="min-w-0 flex-1">
                <p className="font-semibold text-texto text-[1.0625rem] leading-snug">{servico.nome}</p>
                <p className="text-sm text-texto-2 mt-1">
                    {formatarDuracao(servico.duracao_em_minutos)} · {formatarPreco(servico.preco)}
                </p>
            </div>

            <span
                aria-hidden="true"
                className={`size-6 shrink-0 rounded-full flex items-center justify-center transition duration-150 ${
                    selecionado ? 'bg-acento text-sobre-acento' : 'border-2 border-separador'
                }`}
            >
                {selecionado && <Check size={15} strokeWidth={3} />}
            </span>
        </div>
    );
}
