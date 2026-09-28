import { LogOut } from 'lucide-react';
import { useLogout } from '../hooks/useLogout';

export default function LogoutButton() {
    const handleLogout = useLogout();

    return (
        <button
            onClick={handleLogout}
            className="flex items-center gap-2 text-sm text-gray-600 hover:text-black transition-colors"
        >
            <LogOut size={18} />
            <span>Sair</span>
        </button>
    );
}
