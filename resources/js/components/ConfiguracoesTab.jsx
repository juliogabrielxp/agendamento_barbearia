import { useEffect, useState } from 'react';
import api from '../lib/api';
import LogoutButton from './LogoutButton';
import SeletorDeImagem from './SeletorDeImagem';
import AtivarNotificacoes from './AtivarNotificacoes';

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
    const [imagemUrl, setImagemUrl] = useState(null);

    useEffect(() => {
        Promise.all([
            api.get('/api/perfil-estabelecimento'),
            api.get('/api/configuracao-estabelecimento'),
        ]).then(([perfilResponse, configResponse]) => {
            setImagemUrl(perfilResponse.data.imagem_url ?? null);
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

    async function enviarFoto(arquivo) {

      const formData = new FormData();
      formData.append('imagem', arquivo);

      await api.post('/api/perfil-estabelecimento/imagem', formData);
    }

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
            await api.put('/api/perfil-estabelecimento', form);
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
            await api.put('/api/configuracao-estabelecimento', payload);
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
        return <p className="text-texto-2 text-base text-center py-10">Carregando…</p>;
    }

    return (
        <div className="space-y-6">
            <div className="cartao p-4">
                <SeletorDeImagem
                    imagemAtualUrl={imagemUrl}
                    nome={form.nome}
                    tamanho={80}
                    rotulo="Alterar logo"
                    onSelecionar={enviarFoto}
                />
            </div>

            <AtivarNotificacoes />

            <div>
                <h1 className="titulo mb-4">Dados do estabelecimento</h1>

                <form onSubmit={handleSubmit} className="cartao p-4 space-y-4">
                    <div>
                        <label htmlFor="config-nome" className="rotulo">Nome</label>
                        <input
                            type="text"
                            id="config-nome"
                            value={form.nome}
                            onChange={e => handleChange('nome', e.target.value)}
                            className="campo"
                        />
                        {erros.nome && <p role="alert" className="nota-erro mt-1">{erros.nome[0]}</p>}
                    </div>

                    <div>
                        <label htmlFor="config-telefone" className="rotulo">Telefone</label>
                        <input
                            type="text"
                            id="config-telefone"
                            value={form.telefone}
                            onChange={e => handleChange('telefone', e.target.value)}
                            className="campo"
                        />
                        {erros.telefone && <p role="alert" className="nota-erro mt-1">{erros.telefone[0]}</p>}
                    </div>

                    <div>
                        <label htmlFor="config-email" className="rotulo">E-mail</label>
                        <input
                            type="email"
                            id="config-email"
                            value={form.email}
                            onChange={e => handleChange('email', e.target.value)}
                            className="campo"
                        />
                        {erros.email && <p role="alert" className="nota-erro mt-1">{erros.email[0]}</p>}
                    </div>

                    <div>
                        <label htmlFor="config-endereco" className="rotulo">Endereço</label>
                        <input
                            type="text"
                            id="config-endereco"
                            value={form.endereco}
                            onChange={e => handleChange('endereco', e.target.value)}
                            className="campo"
                        />
                        {erros.endereco && <p role="alert" className="nota-erro mt-1">{erros.endereco[0]}</p>}
                    </div>

                    {erros.geral && <p role="alert" className="nota-erro">{erros.geral}</p>}
                    {sucesso && <p role="status" className="text-sm font-medium text-sucesso">Dados atualizados com sucesso.</p>}

                    <button
                        type="submit"
                        disabled={salvando}
                        className="btn btn-primario w-full"
                    >
                        {salvando ? 'Salvando…' : 'Salvar alterações'}
                    </button>
                </form>
            </div>

            <div>
                <h2 className="titulo mb-4">Horário de funcionamento</h2>

                <form onSubmit={handleSubmitHorario} className="cartao p-4 space-y-4">
                    <div className="grid grid-cols-2 gap-3">
                        <div>
                            <label htmlFor="config-hora_abertura" className="rotulo">Abertura</label>
                            <input
                                type="time"
                                id="config-hora_abertura"
                                value={horario.hora_abertura}
                                onChange={e => handleChangeHorario('hora_abertura', e.target.value)}
                                className="campo"
                            />
                            {errosHorario.hora_abertura && <p role="alert" className="nota-erro mt-1">{errosHorario.hora_abertura[0]}</p>}
                        </div>
                        <div>
                            <label htmlFor="config-hora_fechamento" className="rotulo">Fechamento</label>
                            <input
                                type="time"
                                id="config-hora_fechamento"
                                value={horario.hora_fechamento}
                                onChange={e => handleChangeHorario('hora_fechamento', e.target.value)}
                                className="campo"
                            />
                            {errosHorario.hora_fechamento && <p role="alert" className="nota-erro mt-1">{errosHorario.hora_fechamento[0]}</p>}
                        </div>
                    </div>

                    <div className="grid grid-cols-2 gap-3">
                        <div>
                            <label htmlFor="config-intervalo_inicio" className="rotulo">Intervalo (início)</label>
                            <input
                                type="time"
                                id="config-intervalo_inicio"
                                value={horario.intervalo_inicio}
                                onChange={e => handleChangeHorario('intervalo_inicio', e.target.value)}
                                className="campo"
                            />
                            {errosHorario.intervalo_inicio && <p role="alert" className="nota-erro mt-1">{errosHorario.intervalo_inicio[0]}</p>}
                        </div>
                        <div>
                            <label htmlFor="config-intervalo_fim" className="rotulo">Intervalo (fim)</label>
                            <input
                                type="time"
                                id="config-intervalo_fim"
                                value={horario.intervalo_fim}
                                onChange={e => handleChangeHorario('intervalo_fim', e.target.value)}
                                className="campo"
                            />
                            {errosHorario.intervalo_fim && <p role="alert" className="nota-erro mt-1">{errosHorario.intervalo_fim[0]}</p>}
                        </div>
                    </div>

                    {errosHorario.geral && <p role="alert" className="nota-erro">{errosHorario.geral}</p>}
                    {sucessoHorario && <p role="status" className="text-sm font-medium text-sucesso">Horário atualizado com sucesso.</p>}

                    <button
                        type="submit"
                        disabled={salvandoHorario}
                        className="btn btn-primario w-full"
                    >
                        {salvandoHorario ? 'Salvando…' : 'Salvar horário'}
                    </button>
                </form>
            </div>

            <div className="cartao px-4 py-1 flex justify-center md:hidden">
                <LogoutButton />
            </div>
        </div>
    );
}
