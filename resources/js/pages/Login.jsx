import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import api from '../lib/api';
import ImagemComFallback from '../components/ImagemComFallback';
import { lerPendente } from '../lib/agendamentoPendente';

const INPUT_CLASSE =
    'w-full bg-white border border-neutral-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-neutral-900';

export default function Login() {
    const [estabelecimento, setEstabelecimento] = useState(null);
    const [modo, setModo] = useState('entrar');
    const [form, setForm] = useState({ nome: '', email: '', password: '', password_confirmation: '' });
    const [erros, setErros] = useState(() =>
        new URLSearchParams(window.location.search).get('erro') === 'google-email-em-uso'
            ? { geral: ['Já existe uma conta com este e-mail. Entre com e-mail e senha.'] }
            : {}
    );
    const [enviando, setEnviando] = useState(false);

    const criando = modo === 'criar';
    const erro = campo => erros[campo]?.[0];
    const temHorarioGuardado = lerPendente() !== null;

    useEffect(() => {
        api.get('/api/me')
            .then(({ data }) => window.location.replace(data.barbearia ? '/app/painel' : '/app'))
            .catch(() => {});

        api.get('/api/estabelecimento')
            .then(({ data }) => setEstabelecimento(data))
            .catch(() => {});
    }, []);

    function handleChange(campo, valor) {
        setForm(prev => ({ ...prev, [campo]: valor }));
    }

    function alternarModo() {
        setModo(criando ? 'entrar' : 'criar');
        setErros({});
    }

    async function handleSubmit(e) {
        e.preventDefault();
        setEnviando(true);
        setErros({});

        try {
            await api.get('/sanctum/csrf-cookie');

            if (criando) {
                await api.post('/cadastro-cliente', form);
            } else {
                await api.post('/login-cliente', { email: form.email, password: form.password });
            }

            // A vitrine retoma o horário guardado, se houver
            window.location.href = '/app';
        } catch (error) {
            if (error.response?.status === 422) {
                setErros(error.response.data.errors ?? {});
            } else if (error.response?.status === 429) {
                setErros({ geral: ['Muitas tentativas. Aguarde um minuto.'] });
            } else {
                setErros({ geral: ['Não foi possível continuar. Tente novamente.'] });
            }
        } finally {
            setEnviando(false);
        }
    }

    return (
        <div className="min-h-screen bg-neutral-50 px-6 py-6">
            <div className="max-w-sm mx-auto w-full">
                <Link to="/app" className="text-sm text-neutral-400 hover:text-neutral-700 transition">
                    ← Voltar aos serviços
                </Link>

                <div className="flex flex-col items-center text-center mt-8 mb-8">
                    {estabelecimento && (
                        <ImagemComFallback src={estabelecimento.imagem_url} nome={estabelecimento.nome} tamanho={64} />
                    )}
                    <h1 className="text-xl font-semibold text-neutral-900 mt-4">
                        {criando ? 'Crie sua conta' : 'Entre na sua conta'}
                    </h1>
                    <p className="text-sm text-neutral-500 mt-2">
                        {temHorarioGuardado
                            ? 'Falta pouco: seu horário está guardado e você confirma logo depois de entrar.'
                            : `Para agendar e acompanhar seus horários${estabelecimento ? ` no ${estabelecimento.nome}` : ''}.`}
                    </p>
                </div>

                <button
                    onClick={() => { window.location.href = '/auth/google/cliente/redirect'; }}
                    className="w-full flex items-center justify-center gap-3 bg-white border border-neutral-200 text-neutral-900 py-3 rounded-lg text-sm font-medium hover:border-neutral-400 transition"
                >
                    <GoogleIcon />
                    Continuar com Google
                </button>

                <div className="flex items-center gap-3 my-5">
                    <div className="flex-1 h-px bg-neutral-200" />
                    <span className="text-xs text-neutral-400">ou com e-mail</span>
                    <div className="flex-1 h-px bg-neutral-200" />
                </div>

                <form onSubmit={handleSubmit} className="space-y-3">
                    {criando && (
                        <div>
                            <input
                                type="text"
                                placeholder="Seu nome"
                                value={form.nome}
                                onChange={e => handleChange('nome', e.target.value)}
                                required
                                autoComplete="name"
                                className={INPUT_CLASSE}
                            />
                            {erro('nome') && <p className="text-xs text-red-600 mt-1">{erro('nome')}</p>}
                        </div>
                    )}

                    <div>
                        <input
                            type="email"
                            placeholder="E-mail"
                            value={form.email}
                            onChange={e => handleChange('email', e.target.value)}
                            required
                            autoComplete="email"
                            className={INPUT_CLASSE}
                        />
                        {erro('email') && <p className="text-xs text-red-600 mt-1">{erro('email')}</p>}
                    </div>

                    <div>
                        <input
                            type="password"
                            placeholder="Senha"
                            value={form.password}
                            onChange={e => handleChange('password', e.target.value)}
                            required
                            autoComplete={criando ? 'new-password' : 'current-password'}
                            className={INPUT_CLASSE}
                        />
                        {erro('password') && <p className="text-xs text-red-600 mt-1">{erro('password')}</p>}
                    </div>

                    {criando && (
                        <input
                            type="password"
                            placeholder="Confirme a senha"
                            value={form.password_confirmation}
                            onChange={e => handleChange('password_confirmation', e.target.value)}
                            required
                            autoComplete="new-password"
                            className={INPUT_CLASSE}
                        />
                    )}

                    {erro('geral') && <p className="text-xs text-red-600">{erro('geral')}</p>}

                    <button
                        type="submit"
                        disabled={enviando}
                        className="w-full bg-neutral-900 text-white py-3 rounded-lg text-sm font-medium hover:bg-neutral-700 transition disabled:opacity-50"
                    >
                        {enviando ? 'Aguarde...' : criando ? 'Criar conta' : 'Entrar'}
                    </button>
                </form>

                <button
                    onClick={alternarModo}
                    className="w-full text-sm text-neutral-500 hover:text-neutral-800 transition mt-4"
                >
                    {criando ? 'Já tem conta? Entrar' : 'Primeira vez aqui? Criar conta'}
                </button>
            </div>
        </div>
    );
}

function GoogleIcon() {
    return (
        <svg width="16" height="16" viewBox="0 0 48 48" aria-hidden="true">
            <path fill="#FFC107" d="M43.6 20.5H42V20H24v8h11.3c-1.6 4.6-6 8-11.3 8-6.6 0-12-5.4-12-12s5.4-12 12-12c3.1 0 5.9 1.2 8 3.1l6-6C34.6 5.1 29.6 3 24 3 12.4 3 3 12.4 3 24s9.4 21 21 21 21-9.4 21-21c0-1.4-.1-2.7-.4-3.5z"/>
            <path fill="#FF3D00" d="M6.3 14.7l6.6 4.8C14.6 15.9 18.9 13 24 13c3.1 0 5.9 1.2 8 3.1l6-6C34.6 6.1 29.6 4 24 4 16.3 4 9.7 8.3 6.3 14.7z"/>
            <path fill="#4CAF50" d="M24 44c5.5 0 10.4-1.9 14.3-5.1l-6.6-5.4C29.6 35.5 27 36.5 24 36.5c-5.2 0-9.6-3.3-11.3-8l-6.6 5.1C9.6 39.6 16.3 44 24 44z"/>
            <path fill="#1976D2" d="M43.6 20.5H42V20H24v8h11.3c-.8 2.3-2.2 4.2-4.1 5.6l6.6 5.4C41.5 35.9 44 30.5 44 24c0-1.4-.1-2.7-.4-3.5z"/>
        </svg>
    );
}
