import { useEffect, useState } from 'react';
import { useLocation, useNavigate } from 'react-router-dom';
import { CircleAlert, CircleCheck } from 'lucide-react';
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
        return <div className="min-h-dvh flex items-center justify-center text-texto-2 text-base">Carregando…</div>;
    }

    if (erro || !dados) {
        return (
            <div className="min-h-dvh flex flex-col items-center justify-center gap-2 px-6">
                <p className="text-texto-2 text-base text-center">Não foi possível carregar seus dados. Faça login novamente.</p>
                <a href="/app/entrar" className="btn btn-primario mt-2">Entrar</a>
            </div>
        );
    }

    return (
        <div className="min-h-dvh">
            <header className="material fixed top-0 inset-x-0 md:left-56 z-10 h-16 border-b border-separador px-4 md:px-8 flex items-center gap-3">
                {dados.user.avatar && <img src={dados.user.avatar} alt="" className="size-9 rounded-full" />}
                <div className="min-w-0">
                    <p className="font-semibold text-texto text-base leading-tight truncate">{dados.user.nome}</p>
                    <p className="text-sm text-texto-2 leading-tight truncate">{dados.user.email}</p>
                </div>
            </header>

            <NavBarCliente abaAtiva={abaAtiva} onMudarAba={mudarAba} />

            {feedback && (
                <div className="fixed top-20 inset-x-4 md:left-64 md:right-auto md:max-w-md z-20">
                    <div role="status" className="surgir material flex items-center gap-3 rounded-2xl px-4 py-3.5 text-base font-medium text-texto shadow-flutuante">
                        {feedback.tipo === 'sucesso'
                            ? <CircleCheck size={22} className="text-sucesso shrink-0" />
                            : <CircleAlert size={22} className="text-perigo shrink-0" />}
                        {feedback.texto}
                    </div>
                </div>
            )}

            <main className="pt-22 pb-[calc(5.5rem+env(safe-area-inset-bottom))] md:pb-10 px-4 md:pl-64 md:pr-8 max-w-2xl md:max-w-4xl">
                {abaAtiva === 'meus-agendamentos' && <MeusAgendamentosTab />}
                {abaAtiva === 'perfil' && <PerfilTab usuario={dados.user} />}
            </main>
        </div>
    );
}
