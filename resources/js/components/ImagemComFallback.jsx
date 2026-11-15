import { useEffect, useState } from 'react';

export default function ImagemComFallback({ src, nome = '', tamanho = 48, redonda = true }) {
    const [falhou, setFalhou] = useState(false);

    useEffect(() => { setFalhou(false); }, [src]);

    // O canto acompanha o tamanho: uma miniatura de 48 e um logo de 96 ficam com a mesma curva
    const estilo = {
        width: tamanho,
        height: tamanho,
        borderRadius: redonda ? '50%' : Math.round(tamanho * 0.22),
    };

    if (!src || falhou) {
        return (
            <div
                style={{ ...estilo, fontSize: Math.round(tamanho * 0.4) }}
                className="bg-preenchimento text-texto-2 flex items-center justify-center font-semibold shrink-0"
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
            className="object-cover border border-separador shrink-0"
        />
    );
}
