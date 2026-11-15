import { useEffect, useState } from 'react';
import { createPortal } from 'react-dom';
import { X } from 'lucide-react';
import ImagemComFallback from './ImagemComFallback';

export default function ImagemAmpliavel({ src, nome = '', tamanho = 48, redonda = true }) {
    const [aberta, setAberta] = useState(false);

    useEffect(() => {
        if (!aberta) return undefined;

        function aoApertarTecla(event) {
            if (event.key === 'Escape') setAberta(false);
        }

        const overflowAnterior = document.body.style.overflow;
        document.body.style.overflow = 'hidden';
        document.addEventListener('keydown', aoApertarTecla);

        return () => {
            document.body.style.overflow = overflowAnterior;
            document.removeEventListener('keydown', aoApertarTecla);
        };
    }, [aberta]);

    function abrir(event) {
        event.stopPropagation();
        setAberta(true);
    }

    function fechar(event) {
        event.stopPropagation();
        setAberta(false);
    }

    if (!src) {
        return <ImagemComFallback src={null} nome={nome} tamanho={tamanho} redonda={redonda} />;
    }

    return (
        <>
            <button
                type="button"
                onClick={abrir}
                className="shrink-0 cursor-zoom-in transition-transform duration-150 active:scale-95"
                aria-label={`Ampliar imagem de ${nome}`}
            >
                <ImagemComFallback src={src} nome={nome} tamanho={tamanho} redonda={redonda} />
            </button>

            {aberta && createPortal(
                <div
                    className="esmaecer fixed inset-0 z-50 bg-black/70 backdrop-blur-xl flex items-center justify-center p-4"
                    onClick={fechar}
                    role="dialog"
                    aria-modal="true"
                    aria-label={nome}
                >
                    <button
                        type="button"
                        onClick={fechar}
                        autoFocus
                        className="absolute top-4 right-4 size-11 flex items-center justify-center rounded-full bg-white/15 text-white cursor-pointer transition hover:bg-white/25 active:scale-90"
                        aria-label="Fechar"
                    >
                        <X size={22} />
                    </button>
                    <img
                        src={src}
                        alt={nome}
                        onClick={event => event.stopPropagation()}
                        className="materializar max-h-[85vh] max-w-full rounded-2xl object-contain"
                    />
                </div>,
                document.body
            )}
        </>
    );
}
