import { CalendarPlus, CalendarCheck } from 'lucide-react';

const ITENS = [
    { id: 'agendar', label: 'Agendar', Icon: CalendarPlus },
    { id: 'meus-agendamentos', label: 'Meus Agendamentos', Icon: CalendarCheck },
];

export default function NavBarCliente({ abaAtiva, onMudarAba }) {
    return (
        <>
            <nav className="md:hidden fixed bottom-0 inset-x-0 bg-white border-t border-neutral-200 flex z-10">
                {ITENS.map(({ id, label, Icon }) => (
                    <button
                        key={id}
                        onClick={() => onMudarAba(id)}
                        className={`flex-1 flex flex-col items-center gap-1 py-2.5 text-xs font-medium transition ${
                            abaAtiva === id ? 'text-neutral-900' : 'text-neutral-400'
                        }`}
                    >
                        <Icon size={20} strokeWidth={abaAtiva === id ? 2.5 : 2} />
                        {label}
                    </button>
                ))}
            </nav>

            <nav className="hidden md:flex md:flex-col md:fixed md:inset-y-0 md:left-0 md:w-56 bg-white border-r border-neutral-200 pt-20 px-3 gap-1">
                {ITENS.map(({ id, label, Icon }) => (
                    <button
                        key={id}
                        onClick={() => onMudarAba(id)}
                        className={`flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition ${
                            abaAtiva === id ? 'bg-neutral-900 text-white' : 'text-neutral-600 hover:bg-neutral-100'
                        }`}
                    >
                        <Icon size={18} />
                        {label}
                    </button>
                ))}
            </nav>
        </>
    );
}
