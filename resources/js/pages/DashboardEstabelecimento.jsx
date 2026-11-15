import { useEffect, useState } from 'react';
import api from '../lib/api';
import NavBar from '../components/NavBar';
import ServicosTab from '../components/ServicosTab';
import ProfissionaisTab from '../components/ProfissionaisTab';
import AgendaTab from '../components/AgendaTab';
import ConfiguracoesTab from '../components/ConfiguracoesTab';

export default function DashboardEstabelecimento() {
    const [dados, setDados] = useState(null);
    const [carregando, setCarregando] = useState(true);
    const [erro, setErro] = useState(false);
    const [abaAtiva, setAbaAtiva] = useState('agenda');

    useEffect(() => {
        api.get('/api/me')
            .then(response => setDados(response.data))
            .catch(() => setErro(true))
            .finally(() => setCarregando(false));
    }, []);

    if (carregando) {
        return <div className="min-h-dvh flex items-center justify-center text-texto-2 text-base">Carregando…</div>;
    }

    if (erro || !dados) {
        return (
            <div className="min-h-dvh flex flex-col items-center justify-center gap-2 px-6">
                <p className="text-texto-2 text-base text-center">Não foi possível carregar seus dados. Faça login novamente.</p>
                <a href="/app/painel/entrar" className="btn btn-primario mt-2">Entrar</a>
            </div>
        );
    }

    return (
        <div className="min-h-dvh">
            <header className="material fixed top-0 inset-x-0 md:left-56 z-10 h-16 border-b border-separador px-4 md:px-8 flex items-center gap-3">
                {dados.user.avatar && <img src={dados.user.avatar} alt="" className="size-9 rounded-full" />}
                <div className="min-w-0">
                    <p className="font-semibold text-texto text-base leading-tight truncate">{dados.estabelecimento?.nome ?? dados.user.nome}</p>
                    <p className="text-sm text-texto-2 leading-tight truncate">{dados.user.email}</p>
                </div>
            </header>

            <NavBar abaAtiva={abaAtiva} onMudarAba={setAbaAtiva} />

            <main className="pt-22 pb-[calc(5.5rem+env(safe-area-inset-bottom))] md:pb-10 px-4 md:pl-64 md:pr-8 max-w-2xl md:max-w-4xl">
                {abaAtiva === 'servicos' && <ServicosTab />}
                {abaAtiva === 'profissionais' && <ProfissionaisTab />}
                {abaAtiva === 'agenda' && <AgendaTab />}
                {abaAtiva === 'configuracoes' && <ConfiguracoesTab />}
            </main>
        </div>
    );
}
