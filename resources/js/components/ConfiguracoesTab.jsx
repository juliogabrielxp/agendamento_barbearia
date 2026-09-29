import { useEffect, useState } from 'react';
import api from '../lib/api';
import LogoutButton from './LogoutButton';

export default function ConfiguracoesTab() {
    const [form, setForm] = useState({ nome: '', telefone: '', email: '', endereco: '' });
    const [carregando, setCarregando] = useState(true);
    const [salvando, setSalvando] = useState(false);
    const [erros, setErros] = useState({});
    const [sucesso, setSucesso] = useState(false);

    useEffect(() => {
        api.get('/api/perfil-barbearia')
            .then(response => setForm({
                nome: response.data.nome ?? '',
                telefone: response.data.telefone ?? '',
                email: response.data.email ?? '',
                endereco: response.data.endereco ?? '',
            }))
            .finally(() => setCarregando(false));
    }, []);

    function handleChange(campo, valor) {
        setForm(prev => ({ ...prev, [campo]: valor }));
        setSucesso(false);
    }

    async function handleSubmit(e) {
        e.preventDefault();
        setSalvando(true);
        setErros({});
        setSucesso(false);

        try {
            await api.put('/api/perfil-barbearia', form);
            setSucesso(true);
        } catch (error) {
            if (error.response?.status === 422) {
                const dados = error.response.data;
                setErros(dados.errors ?? { geral: dados.message });
            }
        } finally {
            setSalvando(false);
        }
    }

    if (carregando) {
        return <p className="text-neutral-400 text-sm text-center py-8">Carregando...</p>;
    }

    return (
        <div>
            <h1 className="text-lg font-semibold text-neutral-900 mb-4">Configurações</h1>

            <form onSubmit={handleSubmit} className="bg-white border border-neutral-200 rounded-xl p-4 space-y-4">
                <div>
                    <label className="text-xs font-medium text-neutral-500 block mb-1.5">Nome</label>
                    <input
                        type="text"
                        value={form.nome}
                        onChange={e => handleChange('nome', e.target.value)}
                        className="w-full border border-neutral-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-neutral-900"
                    />
                    {erros.nome && <p className="text-xs text-red-600 mt-1">{erros.nome[0]}</p>}
                </div>

                <div>
                    <label className="text-xs font-medium text-neutral-500 block mb-1.5">Telefone</label>
                    <input
                        type="text"
                        value={form.telefone}
                        onChange={e => handleChange('telefone', e.target.value)}
                        className="w-full border border-neutral-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-neutral-900"
                    />
                    {erros.telefone && <p className="text-xs text-red-600 mt-1">{erros.telefone[0]}</p>}
                </div>

                <div>
                    <label className="text-xs font-medium text-neutral-500 block mb-1.5">E-mail</label>
                    <input
                        type="email"
                        value={form.email}
                        onChange={e => handleChange('email', e.target.value)}
                        className="w-full border border-neutral-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-neutral-900"
                    />
                    {erros.email && <p className="text-xs text-red-600 mt-1">{erros.email[0]}</p>}
                </div>

                <div>
                    <label className="text-xs font-medium text-neutral-500 block mb-1.5">Endereço</label>
                    <input
                        type="text"
                        value={form.endereco}
                        onChange={e => handleChange('endereco', e.target.value)}
                        className="w-full border border-neutral-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-neutral-900"
                    />
                    {erros.endereco && <p className="text-xs text-red-600 mt-1">{erros.endereco[0]}</p>}
                </div>

                {erros.geral && <p className="text-xs text-red-600">{erros.geral}</p>}
                {sucesso && <p className="text-xs text-green-600">Dados atualizados com sucesso.</p>}

                <button
                    type="submit"
                    disabled={salvando}
                    className="w-full bg-neutral-900 text-white text-sm font-medium rounded-lg py-2.5 disabled:opacity-50"
                >
                    {salvando ? 'Salvando...' : 'Salvar alterações'}
                </button>
            </form>

            <div className="mt-6 bg-white border border-neutral-200 rounded-xl p-4">
                <LogoutButton />
            </div>
        </div>
    );
}
