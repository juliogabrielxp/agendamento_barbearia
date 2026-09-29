import { useEffect, useState } from 'react';
import api from '../lib/api';
import LogoutButton from './LogoutButton';

export default function ConfiguracoesTab() {
    const [form, setForm] = useState({ nome: '', telefone: '', email: '', endereco: '' });
    const [horario, setHorario] = useState({ hora_abertura: '', hora_fechamento: '', intervalo_inicio: '', intervalo_fim: '' });
    const [carregando, setCarregando] = useState(true);
    const [salvando, setSalvando] = useState(false);
    const [salvandoHorario, setSalvandoHorario] = useState(false);
    const [erros, setErros] = useState({});
    const [errosHorario, setErrosHorario] = useState({});
    const [sucesso, setSucesso] = useState(false);
    const [sucessoHorario, setSucessoHorario] = useState(false);

    useEffect(() => {
        Promise.all([
            api.get('/api/perfil-barbearia'),
            api.get('/api/configuracao-barbearia'),
        ]).then(([perfilResponse, configResponse]) => {
            setForm({
                nome: perfilResponse.data.nome ?? '',
                telefone: perfilResponse.data.telefone ?? '',
                email: perfilResponse.data.email ?? '',
                endereco: perfilResponse.data.endereco ?? '',
            });
            setHorario({
                hora_abertura: (configResponse.data.hora_abertura ?? '').slice(0, 5),
                hora_fechamento: (configResponse.data.hora_fechamento ?? '').slice(0, 5),
                intervalo_inicio: (configResponse.data.intervalo_inicio ?? '').slice(0, 5),
                intervalo_fim: (configResponse.data.intervalo_fim ?? '').slice(0, 5),
            });
        }).finally(() => setCarregando(false));
    }, []);

    function handleChange(campo, valor) {
        setForm(prev => ({ ...prev, [campo]: valor }));
        setSucesso(false);
    }

    function handleChangeHorario(campo, valor) {
        setHorario(prev => ({ ...prev, [campo]: valor }));
        setSucessoHorario(false);
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

    async function handleSubmitHorario(e) {
        e.preventDefault();
        setSalvandoHorario(true);
        setErrosHorario({});
        setSucessoHorario(false);

        const payload = {
            hora_abertura: horario.hora_abertura,
            hora_fechamento: horario.hora_fechamento,
            intervalo_inicio: horario.intervalo_inicio || null,
            intervalo_fim: horario.intervalo_fim || null,
        };

        try {
            await api.put('/api/configuracao-barbearia', payload);
            setSucessoHorario(true);
        } catch (error) {
            if (error.response?.status === 422) {
                const dados = error.response.data;
                setErrosHorario(dados.errors ?? { geral: dados.message });
            }
        } finally {
            setSalvandoHorario(false);
        }
    }

    if (carregando) {
        return <p className="text-neutral-400 text-sm text-center py-8">Carregando...</p>;
    }

    return (
        <div className="space-y-6">
            <div>
                <h1 className="text-lg font-semibold text-neutral-900 mb-4">Dados da barbearia</h1>

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
            </div>

            <div>
                <h2 className="text-lg font-semibold text-neutral-900 mb-4">Horário de funcionamento</h2>

                <form onSubmit={handleSubmitHorario} className="bg-white border border-neutral-200 rounded-xl p-4 space-y-4">
                    <div className="grid grid-cols-2 gap-3">
                        <div>
                            <label className="text-xs font-medium text-neutral-500 block mb-1.5">Abertura</label>
                            <input
                                type="time"
                                value={horario.hora_abertura}
                                onChange={e => handleChangeHorario('hora_abertura', e.target.value)}
                                className="w-full border border-neutral-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-neutral-900"
                            />
                            {errosHorario.hora_abertura && <p className="text-xs text-red-600 mt-1">{errosHorario.hora_abertura[0]}</p>}
                        </div>
                        <div>
                            <label className="text-xs font-medium text-neutral-500 block mb-1.5">Fechamento</label>
                            <input
                                type="time"
                                value={horario.hora_fechamento}
                                onChange={e => handleChangeHorario('hora_fechamento', e.target.value)}
                                className="w-full border border-neutral-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-neutral-900"
                            />
                            {errosHorario.hora_fechamento && <p className="text-xs text-red-600 mt-1">{errosHorario.hora_fechamento[0]}</p>}
                        </div>
                    </div>

                    <div className="grid grid-cols-2 gap-3">
                        <div>
                            <label className="text-xs font-medium text-neutral-500 block mb-1.5">Intervalo (início)</label>
                            <input
                                type="time"
                                value={horario.intervalo_inicio}
                                onChange={e => handleChangeHorario('intervalo_inicio', e.target.value)}
                                className="w-full border border-neutral-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-neutral-900"
                            />
                            {errosHorario.intervalo_inicio && <p className="text-xs text-red-600 mt-1">{errosHorario.intervalo_inicio[0]}</p>}
                        </div>
                        <div>
                            <label className="text-xs font-medium text-neutral-500 block mb-1.5">Intervalo (fim)</label>
                            <input
                                type="time"
                                value={horario.intervalo_fim}
                                onChange={e => handleChangeHorario('intervalo_fim', e.target.value)}
                                className="w-full border border-neutral-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-neutral-900"
                            />
                            {errosHorario.intervalo_fim && <p className="text-xs text-red-600 mt-1">{errosHorario.intervalo_fim[0]}</p>}
                        </div>
                    </div>

                    {errosHorario.geral && <p className="text-xs text-red-600">{errosHorario.geral}</p>}
                    {sucessoHorario && <p className="text-xs text-green-600">Horário atualizado com sucesso.</p>}

                    <button
                        type="submit"
                        disabled={salvandoHorario}
                        className="w-full bg-neutral-900 text-white text-sm font-medium rounded-lg py-2.5 disabled:opacity-50"
                    >
                        {salvandoHorario ? 'Salvando...' : 'Salvar horário'}
                    </button>
                </form>
            </div>

            <div className="bg-white border border-neutral-200 rounded-xl p-4">
                <LogoutButton />
            </div>
        </div>
    );
}
