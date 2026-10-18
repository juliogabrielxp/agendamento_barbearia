import { useEffect, useState } from 'react';

export default function ImagemComFallback({ src, nome = '', tamanho = 48, redonda = true }) {
    const [falhou, setFalhou] = useState(false);

    useEffect(() => { setFalhou(false); }, [src]);

    const formato = redonda ? 'rounded-full' : 'rounded-lg';
    const estilo = { width: tamanho, height: tamanho };

    if (!src || falhou) {
        return (
            <div
                style={estilo}
                className={`${formato} bg-neutral-100 text-neutral-500 flex items-center justify-center text-sm font-semibold shrink-0`}
            >
                {nome.trim().charAt(0).toUpperCase() || '?'}
            </div>
        );
    }

    return (
        <img
            src={src}
            alt={nome}
            style={estilo}
            onError={() => setFalhou(true)}
            className={`${formato} object-cover border border-neutral-200 shrink-0`}
        />
    );
}
