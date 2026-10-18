import { useEffect, useRef, useState } from 'react';
import { Camera } from 'lucide-react';
import ImagemComFallback from './ImagemComFallback';

const TIPOS_ACEITOS = ['image/jpeg', 'image/png', 'image/webp'];
const TAMANHO_MAXIMO_MB = 2;

export default function SeletorDeImagem({
    imagemAtualUrl = null,
    nome = '',
    redonda = true,
    tamanho = 72,
    rotulo = 'Alterar foto',
    onSelecionar,
}) {
    const inputRef = useRef(null);
    const [preview, setPreview] = useState(null);
    const [erro, setErro] = useState(null);
    const [enviando, setEnviando] = useState(false);

    useEffect(() => {
        return () => {
            if (preview) URL.revokeObjectURL(preview);
        };
    }, [preview]);

    async function handleChange(event) {
        const arquivo = event.target.files?.[0];
        event.target.value = '';
        if (!arquivo) return;

        if (!TIPOS_ACEITOS.includes(arquivo.type)) {
            setErro('Use uma imagem JPG, PNG ou WebP.');
            return;
        }

        if (arquivo.size > TAMANHO_MAXIMO_MB * 1024 * 1024) {
            setErro(`A imagem deve ter no máximo ${TAMANHO_MAXIMO_MB} MB.`);
            return;
        }

        setErro(null);
        setPreview(URL.createObjectURL(arquivo));
        setEnviando(true);

        try {
            await onSelecionar(arquivo);
        } catch {
            setPreview(null);
            setErro('Não foi possível enviar a imagem.');
        } finally {
            setEnviando(false);
        }
    }

    return (
        <div>
            <div className="flex items-center gap-4">
                <ImagemComFallback
                    src={preview ?? imagemAtualUrl}
                    nome={nome}
                    tamanho={tamanho}
                    redonda={redonda}
                />
                <div>
                    <button
                        type="button"
                        onClick={() => inputRef.current?.click()}
                        disabled={enviando}
                        className="flex items-center gap-2 text-xs font-medium text-neutral-700 bg-white border border-neutral-200 rounded-lg px-3 py-2 hover:border-neutral-400 transition disabled:opacity-50"
                    >
                        <Camera size={14} />
                        {enviando ? 'Enviando...' : rotulo}
                    </button>
                    <p className="text-xs text-neutral-400 mt-1.5">JPG, PNG ou WebP, até {TAMANHO_MAXIMO_MB} MB</p>
                </div>
            </div>

            {erro && <p className="text-xs text-red-600 mt-2">{erro}</p>}

            <input
                ref={inputRef}
                type="file"
                accept="image/jpeg,image/png,image/webp"
                onChange={handleChange}
                className="hidden"
            />
        </div>
    );
}
