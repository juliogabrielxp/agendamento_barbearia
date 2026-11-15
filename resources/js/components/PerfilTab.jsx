import LogoutButton from './LogoutButton';

export default function PerfilTab({ usuario }) {
    return (
        <div className="cartao p-6 flex flex-col items-center gap-4 text-center">
            {usuario.avatar && (
                <img src={usuario.avatar} alt="" className="w-20 h-20 rounded-full" />
            )}
            <div>
                <p className="titulo">{usuario.nome}</p>
                <p className="text-base text-texto-2 mt-1 break-all">{usuario.email}</p>
            </div>

            <div className="w-full border-t border-separador pt-2 mt-2 flex justify-center">
                <LogoutButton />
            </div>
        </div>
    );
}
