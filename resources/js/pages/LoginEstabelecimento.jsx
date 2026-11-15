import { useState } from 'react';
import { ChevronLeft, LayoutDashboard } from 'lucide-react';
import api from '../lib/api';
import { Link } from 'react-router-dom';

export default function LoginEstabelecimento() {
    const [email, setEmail] = useState('');
    const [password, setPassword] = useState('');
    const [erro, setErro] = useState(null);
    const [enviando, setEnviando] = useState(false);

    async function handleSubmit(e) {
        e.preventDefault();
        setEnviando(true);
        setErro(null);

        try {
            await api.get('/sanctum/csrf-cookie');
            await api.post('/login-estabelecimento', { email, password });
            window.location.href = '/app/painel';
        } catch (error) {
            const mensagem = error.response?.data?.errors?.email?.[0]
                ?? (error.response?.status === 429
                    ? 'Muitas tentativas. Aguarde um minuto.'
                    : 'Não foi possível entrar. Tente novamente.');
            setErro(mensagem);
        } finally {
            setEnviando(false);
        }
    }

    return (
        <div className="min-h-dvh flex flex-col justify-center px-6 py-10">
            <div className="max-w-sm mx-auto w-full">
                <div className="size-14 bg-acento rounded-2xl flex items-center justify-center mb-6">
                    <LayoutDashboard size={26} className="text-sobre-acento" strokeWidth={2} />
                </div>

                <h1 className="titulo-grande mb-2">
                    Área da profissional
                </h1>
                <p className="text-base text-texto-2 mb-8">
                    Entre com o e-mail e a senha do estabelecimento para ver a agenda.
                </p>

                <form onSubmit={handleSubmit} className="space-y-4">
                    <div>
                        <label htmlFor="painel-email" className="rotulo">E-mail</label>
                        <input
                            id="painel-email"
                            type="email"
                            value={email}
                            onChange={e => setEmail(e.target.value)}
                            required
                            autoComplete="email"
                            className="campo"
                        />
                    </div>

                    <div>
                        <label htmlFor="painel-senha" className="rotulo">Senha</label>
                        <input
                            id="painel-senha"
                            type="password"
                            value={password}
                            onChange={e => setPassword(e.target.value)}
                            required
                            autoComplete="current-password"
                            className="campo"
                        />
                    </div>

                    {erro && <p role="alert" className="nota-erro">{erro}</p>}

                    <button
                        type="submit"
                        disabled={enviando}
                        className="btn btn-primario w-full min-h-[3.125rem]"
                    >
                        {enviando ? 'Entrando…' : 'Entrar'}
                    </button>
                </form>
                <Link
                    to="/app"
                    className="btn-texto w-full gap-0.5 mt-4"
                >
                    <ChevronLeft size={20} />
                    Voltar aos serviços
                </Link>
            </div>
        </div>
    );
}
