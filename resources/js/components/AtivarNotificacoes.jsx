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
        <div className="bg-white border border-neutral-200 rounded-xl p-4">
            <div className="flex items-start gap-3">
                <div className="w-9 h-9 rounded-lg bg-neutral-100 flex items-center justify-center shrink-0">
                    {estado === 'ativo'
                        ? <Bell size={18} className="text-neutral-900" />
                        : <BellOff size={18} className="text-neutral-400" />}
                </div>
                <div className="min-w-0 flex-1">
                    <p className="text-sm font-medium text-neutral-900">Notificações de novos agendamentos</p>
                    <p className="text-xs text-neutral-500 mt-0.5">{descricao}</p>
                    {erro && <p className="text-xs text-red-600 mt-1">{erro}</p>}
                </div>
            </div>

            {estado === 'inativo' && (
                <button
                    onClick={ativar}
                    className="mt-3 w-full bg-neutral-900 text-white text-sm font-medium rounded-lg py-2.5 hover:bg-neutral-700 transition"
                >
                    Ativar notificações
                </button>
            )}

            {estado === 'ativo' && (
                <button
                    onClick={desativar}
                    className="mt-3 w-full bg-white border border-neutral-200 text-neutral-700 text-sm font-medium rounded-lg py-2.5 hover:border-neutral-400 transition"
                >
                    Desativar neste aparelho
                </button>
            )}
        </div>
    );
}
