import LogoutButton from './LogoutButton';

export default function PerfilTab({ usuario }) {
    return (
        <div className="bg-white rounded-xl border border-neutral-200 p-6 flex flex-col items-center gap-4 text-center">
            {usuario.avatar && (
                <img src={usuario.avatar} alt="" className="w-20 h-20 rounded-full" />
            )}
            <div>
                <p className="font-semibold text-neutral-900 text-lg">{usuario.nome}</p>
                <p className="text-sm text-neutral-400">{usuario.email}</p>
            </div>

            <div className="w-full border-t border-neutral-200 pt-4 mt-2">
                <LogoutButton />
            </div>
        </div>
    );
}
