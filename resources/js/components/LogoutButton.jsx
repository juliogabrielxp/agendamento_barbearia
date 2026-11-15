import { LogOut } from 'lucide-react';
import { useLogout } from '../hooks/useLogout';

export default function LogoutButton() {
    const handleLogout = useLogout();

    return (
        <button
            onClick={handleLogout}
            className="btn-texto text-perigo hover:text-perigo"
        >
            <LogOut size={18} />
            <span>Sair</span>
        </button>
    );
}
