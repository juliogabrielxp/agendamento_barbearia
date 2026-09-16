import { Scissors, Search, Clock, CalendarCheck } from 'lucide-react';

const BENEFICIOS = [
    { Icon: Search, texto: 'Encontre barbearias perto de você' },
    { Icon: Clock, texto: 'Escolha o profissional e o horário' },
    { Icon: CalendarCheck, texto: 'Sem conflito: seu horário fica garantido' },
];

export default function Login() {
    function entrarComoCliente() {
        window.location.href = '/auth/google/cliente/redirect';
    }

    return (
        <div className="min-h-screen bg-neutral-50 flex flex-col justify-between px-6 py-10">
            <div className="flex-1 flex flex-col justify-center max-w-sm mx-auto w-full">
                <div className="w-12 h-12 bg-neutral-900 rounded-xl flex items-center justify-center mb-6">
                    <Scissors size={22} className="text-white" strokeWidth={2} />
                </div>

                <h1 className="text-2xl font-semibold text-neutral-900 mb-2 leading-tight">
                    Agende seu corte sem complicação
                </h1>
                <p className="text-sm text-neutral-500 mb-10">
                    Encontre uma barbearia, escolha o profissional e marque seu horário em poucos minutos.
                </p>

                <div className="space-y-4 mb-10">
                    {BENEFICIOS.map(({ Icon, texto }) => (
                        <div key={texto} className="flex items-center gap-3">
                            <div className="w-9 h-9 rounded-lg bg-white border border-neutral-200 flex items-center justify-center shrink-0">
                                <Icon size={16} className="text-neutral-700" />
                            </div>
                            <p className="text-sm text-neutral-700">{texto}</p>
                        </div>
                    ))}
                </div>
            </div>

            <div className="max-w-sm mx-auto w-full">
                <button
                    onClick={entrarComoCliente}
                    className="w-full flex items-center justify-center gap-3 bg-neutral-900 text-white py-3.5 rounded-lg text-sm font-medium hover:bg-neutral-700 transition"
                >
                    <GoogleIcon />
                    Entrar com Google
                </button>
                <p className="text-xs text-neutral-400 text-center mt-4">
                    Ao continuar, você concorda em usar sua conta Google para agendamentos.
                </p>
            </div>
        </div>
    );
}

function GoogleIcon() {
    return (
        <svg width="16" height="16" viewBox="0 0 48 48">
            <path fill="#FFC107" d="M43.6 20.5H42V20H24v8h11.3c-1.6 4.6-6 8-11.3 8-6.6 0-12-5.4-12-12s5.4-12 12-12c3.1 0 5.9 1.2 8 3.1l6-6C34.6 5.1 29.6 3 24 3 12.4 3 3 12.4 3 24s9.4 21 21 21 21-9.4 21-21c0-1.4-.1-2.7-.4-3.5z"/>
            <path fill="#FF3D00" d="M6.3 14.7l6.6 4.8C14.6 15.9 18.9 13 24 13c3.1 0 5.9 1.2 8 3.1l6-6C34.6 6.1 29.6 4 24 4 16.3 4 9.7 8.3 6.3 14.7z"/>
            <path fill="#4CAF50" d="M24 44c5.5 0 10.4-1.9 14.3-5.1l-6.6-5.4C29.6 35.5 27 36.5 24 36.5c-5.2 0-9.6-3.3-11.3-8l-6.6 5.1C9.6 39.6 16.3 44 24 44z"/>
            <path fill="#1976D2" d="M43.6 20.5H42V20H24v8h11.3c-.8 2.3-2.2 4.2-4.1 5.6l6.6 5.4C41.5 35.9 44 30.5 44 24c0-1.4-.1-2.7-.4-3.5z"/>
        </svg>
    );
}
