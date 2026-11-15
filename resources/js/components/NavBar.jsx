import { Calendar, Sparkles, Users, Settings } from 'lucide-react';
import LogoutButton from './LogoutButton';

const ITENS = [
    { id: 'agenda', label: 'Agenda', Icon: Calendar },
    { id: 'servicos', label: 'Serviços', Icon: Sparkles },
    { id: 'profissionais', label: 'Profissionais', Icon: Users },
    { id: 'configuracoes', label: 'Config', Icon: Settings },
];

export default function NavBar({ abaAtiva, onMudarAba }) {
    return (
        <>
            <nav className="material md:hidden fixed bottom-0 inset-x-0 z-10 flex border-t border-separador pb-[env(safe-area-inset-bottom)]">
                {ITENS.map(({ id, label, Icon }) => (
                    <button
                        key={id}
                        onClick={() => onMudarAba(id)}
                        aria-current={abaAtiva === id ? 'page' : undefined}
                        className={`flex-1 flex flex-col items-center justify-center gap-0.5 min-h-[3.25rem] pt-1.5 pb-1 text-[0.6875rem] cursor-pointer transition active:opacity-50 ${
                            abaAtiva === id ? 'text-acento font-semibold' : 'text-texto-2 font-medium'
                        }`}
                    >
                        <Icon size={22} strokeWidth={abaAtiva === id ? 2.4 : 1.8} />
                        {label}
                    </button>
                ))}
            </nav>

            <nav className="hidden md:flex md:flex-col md:fixed md:inset-y-0 md:left-0 md:w-56 bg-superficie border-r border-separador pt-5 px-3 gap-1">
                {ITENS.map(({ id, label, Icon }) => (
                    <button
                        key={id}
                        onClick={() => onMudarAba(id)}
                        aria-current={abaAtiva === id ? 'page' : undefined}
                        className={`flex items-center gap-3 px-3 min-h-11 rounded-xl text-base cursor-pointer transition ${
                            abaAtiva === id
                                ? 'bg-preenchimento text-texto font-semibold'
                                : 'text-texto-2 font-medium hover:bg-preenchimento/60 hover:text-texto'
                        }`}
                    >
                        <Icon size={20} />
                        {label}
                    </button>
                ))}

                <div className="mt-auto mb-4 px-3">
                    <LogoutButton />
                </div>
            </nav>
        </>
    );
}
