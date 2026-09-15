import { useEffect, useState } from 'react';
import api from '../lib/api';
import NavBar from '../components/NavBar';
import ServicosTab from '../components/ServicosTab';
import ProfissionaisTab from '../components/ProfissionaisTab';
import AgendaTab from '../components/AgendaTab';

export default function DashboardBarbearia() {
    const [dados, setDados] = useState(null);
    const [carregando, setCarregando] = useState(true);
    const [erro, setErro] = useState(false);
    const [abaAtiva, setAbaAtiva] = useState('servicos');

    useEffect(() => {
        api.get('/api/me')
            .then(response => setDados(response.data))
            .catch(() => setErro(true))
            .finally(() => setCarregando(false));
    }, []);

    if (carregando) {
        return <div className="min-h-screen flex items-center justify-center bg-neutral-50 text-neutral-400 text-sm">Carregando...</div>;
    }

    if (erro || !dados) {
        return (
            <div className="min-h-screen flex flex-col items-center justify-center bg-neutral-50 gap-3 px-4">
                <p className="text-neutral-500 text-sm text-center">Não foi possível carregar seus dados. Faça login novamente.</p>
                <a href="/app" className="text-sm font-medium text-neutral-900 underline">Voltar para o login</a>
            </div>
        );
    }

    return (
        <div className="min-h-screen bg-neutral-50">
            <header className="fixed top-0 inset-x-0 bg-white border-b border-neutral-200 px-4 md:px-6 py-3.5 flex items-center gap-3 z-10">
                {dados.user.avatar && <img src={dados.user.avatar} alt="" className="w-9 h-9 rounded-full" />}
                <div className="min-w-0">
                    <p className="font-semibold text-neutral-900 text-sm truncate">{dados.barbearia?.nome ?? dados.user.nome}</p>
                    <p className="text-xs text-neutral-400 truncate">{dados.user.email}</p>
                </div>
            </header>

            <NavBar abaAtiva={abaAtiva} onMudarAba={setAbaAtiva} />

            <main className="pt-20 pb-24 md:pb-8 px-4 md:pl-64 md:pr-6 max-w-2xl md:max-w-3xl">
                {abaAtiva === 'servicos' && <ServicosTab />}
                {abaAtiva === 'profissionais' && <ProfissionaisTab />}
                {abaAtiva === 'agenda' && <AgendaTab />}
            </main>
        </div>
    );
}
