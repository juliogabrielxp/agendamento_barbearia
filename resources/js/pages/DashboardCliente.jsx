import { useEffect, useState } from 'react';
import { useLocation, useNavigate } from 'react-router-dom';
import api from '../lib/api';
import NavBarCliente from '../components/NavBarCliente';
import MeusAgendamentosTab from '../components/MeusAgendamentosTab';
import PerfilTab from '../components/PerfilTab';

export default function DashboardCliente() {
    const [dados, setDados] = useState(null);
    const [carregando, setCarregando] = useState(true);
    const [erro, setErro] = useState(false);
    const navigate = useNavigate();
    const location = useLocation();
    const [abaAtiva, setAbaAtiva] = useState('meus-agendamentos');
    const [feedback, setFeedback] = useState(() =>
        location.state?.feedback ? { tipo: 'sucesso', texto: location.state.feedback } : null
    );

    useEffect(() => {
        api.get('/api/me')
            .then(response => setDados(response.data))
            .catch(() => setErro(true))
            .finally(() => setCarregando(false));
    }, []);

    useEffect(() => {
        if (!feedback) return;
        const timer = setTimeout(() => setFeedback(null), 4000);
        return () => clearTimeout(timer);
    }, [feedback]);

    useEffect(() => {
        // Não repete o aviso se a pessoa recarregar a página
        if (location.state?.feedback) navigate(location.pathname, { replace: true, state: null });
    }, []);

    function mudarAba(aba) {
        if (aba === 'agendar') {
            navigate('/app');
            return;
        }
        setAbaAtiva(aba);
    }

    if (carregando) {
        return <div className="min-h-screen flex items-center justify-center bg-neutral-50 text-neutral-400 text-sm">Carregando...</div>;
    }

    if (erro || !dados) {
        return (
            <div className="min-h-screen flex flex-col items-center justify-center bg-neutral-50 gap-3 px-4">
                <p className="text-neutral-500 text-sm text-center">Não foi possível carregar seus dados. Faça login novamente.</p>
                <a href="/app/entrar" className="text-sm font-medium text-neutral-900 underline">Entrar</a>
            </div>
        );
    }

    return (
        <div className="min-h-screen bg-neutral-50">
            <header className="fixed top-0 inset-x-0 bg-white border-b border-neutral-200 px-4 md:px-6 py-3.5 flex items-center gap-3 z-10">
                {dados.user.avatar && <img src={dados.user.avatar} alt="" className="w-9 h-9 rounded-full" />}
                <div className="min-w-0">
                    <p className="font-semibold text-neutral-900 text-sm truncate">{dados.user.nome}</p>
                    <p className="text-xs text-neutral-400 truncate">{dados.user.email}</p>
                </div>
            </header>

            <NavBarCliente abaAtiva={abaAtiva} onMudarAba={mudarAba} />

            {feedback && (
                <div className="fixed top-20 inset-x-4 md:left-64 md:right-6 z-20">
                    <div className={`rounded-lg px-4 py-3 text-sm font-medium text-white shadow-lg ${
                        feedback.tipo === 'sucesso' ? 'bg-green-600' : 'bg-red-600'
                    }`}>
                        {feedback.texto}
                    </div>
                </div>
            )}

            <main className="pt-20 pb-24 md:pb-8 px-4 md:pl-64 md:pr-6 max-w-2xl md:max-w-3xl">
                {abaAtiva === 'meus-agendamentos' && <MeusAgendamentosTab />}
                {abaAtiva === 'perfil' && <PerfilTab usuario={dados.user} />}
            </main>
        </div>
    );
}
