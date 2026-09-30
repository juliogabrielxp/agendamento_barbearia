import { useState } from 'react';
import { Scissors } from 'lucide-react';
import api from '../lib/api';

export default function LoginBarbearia() {
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
            await api.post('/login-barbearia', { email, password });
            window.location.href = '/app/dashboard-barbearia';
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
        <div className="min-h-screen bg-neutral-50 flex flex-col justify-center px-6 py-10">
            <div className="max-w-sm mx-auto w-full">
                <div className="w-12 h-12 bg-neutral-900 rounded-xl flex items-center justify-center mb-6">
                    <Scissors size={22} className="text-white" strokeWidth={2} />
                </div>

                <h1 className="text-2xl font-semibold text-neutral-900 mb-2 leading-tight">
                    Acesso da barbearia
                </h1>
                <p className="text-sm text-neutral-500 mb-8">
                    Entre com o e-mail e a senha da sua barbearia.
                </p>

                <form onSubmit={handleSubmit} className="space-y-4">
                    <div>
                        <label className="text-xs font-medium text-neutral-500 block mb-1.5">E-mail</label>
                        <input
                            type="email"
                            value={email}
                            onChange={e => setEmail(e.target.value)}
                            required
                            autoComplete="email"
                            className="w-full bg-white border border-neutral-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-neutral-900"
                        />
                    </div>

                    <div>
                        <label className="text-xs font-medium text-neutral-500 block mb-1.5">Senha</label>
                        <input
                            type="password"
                            value={password}
                            onChange={e => setPassword(e.target.value)}
                            required
                            autoComplete="current-password"
                            className="w-full bg-white border border-neutral-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-neutral-900"
                        />
                    </div>

                    {erro && <p className="text-xs text-red-600">{erro}</p>}

                    <button
                        type="submit"
                        disabled={enviando}
                        className="w-full bg-neutral-900 text-white py-3 rounded-lg text-sm font-medium hover:bg-neutral-700 transition disabled:opacity-50"
                    >
                        {enviando ? 'Entrando...' : 'Entrar'}
                    </button>
                </form>
            </div>
        </div>
    );
}
