import { CalendarPlus, CalendarCheck, User } from 'lucide-react';

const ITENS = [
    { id: 'agendar', label: 'Agendar', Icon: CalendarPlus },
    { id: 'meus-agendamentos', label: 'Meus horários', Icon: CalendarCheck },
    { id: 'perfil', label: 'Perfil', Icon: User },
];

export default function NavBarCliente({ abaAtiva, onMudarAba }) {
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
            </nav>
        </>
    );
}
