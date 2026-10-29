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
                className="shrink-0 cursor-zoom-in"
                aria-label={`Ampliar imagem de ${nome}`}
            >
                <ImagemComFallback src={src} nome={nome} tamanho={tamanho} redonda={redonda} />
            </button>

            {aberta && createPortal(
                <div
                    className="fixed inset-0 z-50 bg-black/80 flex items-center justify-center p-4"
                    onClick={fechar}
                    role="dialog"
                    aria-modal="true"
                    aria-label={nome}
                >
                    <button
                        type="button"
                        onClick={fechar}
                        className="absolute top-4 right-4 p-2 text-white/80 hover:text-white transition"
                        aria-label="Fechar"
                    >
                        <X size={24} />
                    </button>
                    <img
                        src={src}
                        alt={nome}
                        onClick={event => event.stopPropagation()}
                        className="max-h-[85vh] max-w-full rounded-xl object-contain"
                    />
                </div>,
                document.body
            )}
        </>
    );
}
