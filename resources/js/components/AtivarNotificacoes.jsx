import { Bell, BellOff } from 'lucide-react';
import { usePushNotifications } from '../hooks/usePushNotifications';

const ehIOS = /iphone|ipad|ipod/i.test(navigator.userAgent);

const DESCRICOES = {
    ativo: 'Este aparelho recebe um aviso a cada novo agendamento.',
    inativo: 'Receba um aviso neste aparelho sempre que um cliente agendar.',
    bloqueado: 'As notificações estão bloqueadas neste navegador. Libere nas configurações do site e volte aqui.',
};

export default function AtivarNotificacoes() {
    const { estado, erro, ativar, desativar } = usePushNotifications();

    if (estado === 'carregando') return null;

    const descricao = estado === 'nao-suportado'
        ? (ehIOS
            ? 'No iPhone, instale o app primeiro: toque em Compartilhar, depois em Adicionar à Tela de Início, e abra o app por lá.'
            : 'Este navegador não suporta notificações.')
        : DESCRICOES[estado];

    return (
        <div className="cartao p-4">
            <div className="flex items-start gap-3">
                <div className="size-10 rounded-xl bg-preenchimento flex items-center justify-center shrink-0">
                    {estado === 'ativo'
                        ? <Bell size={20} className="text-texto" />
                        : <BellOff size={20} className="text-texto-3" />}
                </div>
                <div className="min-w-0 flex-1">
                    <p className="text-base font-semibold text-texto">Notificações de novos agendamentos</p>
                    <p className="text-sm text-texto-2 mt-0.5">{descricao}</p>
                    {erro && <p role="alert" className="nota-erro mt-1">{erro}</p>}
                </div>
            </div>

            {estado === 'inativo' && (
                <button
                    onClick={ativar}
                    className="btn btn-primario mt-4 w-full"
                >
                    Ativar notificações
                </button>
            )}

            {estado === 'ativo' && (
                <button
                    onClick={desativar}
                    className="btn btn-secundario mt-4 w-full"
                >
                    Desativar neste aparelho
                </button>
            )}
        </div>
    );
}
