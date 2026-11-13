export function formatarPreco(valor) {
    return Number(valor).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
}

export function formatarDuracao(minutos) {
    const horas = Math.floor(minutos / 60);
    const resto = minutos % 60;

    if (horas === 0) return `${resto} min`;
    return resto === 0 ? `${horas} h` : `${horas} h ${resto} min`;
}

// "YYYY-MM-DD" no fuso do aparelho. toISOString() usaria UTC e,
// depois das 21h no Brasil, já mostraria o dia seguinte.
export function dataLocal(data = new Date()) {
    const ano = data.getFullYear();
    const mes = String(data.getMonth() + 1).padStart(2, '0');
    const dia = String(data.getDate()).padStart(2, '0');
    return `${ano}-${mes}-${dia}`;
}

export function proximosDias(quantidade) {
    const hoje = new Date();
    return Array.from({ length: quantidade }, (_, i) => {
        const dia = new Date(hoje.getFullYear(), hoje.getMonth(), hoje.getDate() + i);
        return {
            valor: dataLocal(dia),
            semana: i === 0 ? 'Hoje' : dia.toLocaleDateString('pt-BR', { weekday: 'short' }).replace('.', ''),
            dia: dia.getDate(),
            mes: dia.toLocaleDateString('pt-BR', { month: 'short' }).replace('.', ''),
        };
    });
}

export function formatarDataPorExtenso(valor) {
    const [ano, mes, dia] = valor.split('-').map(Number);
    return new Date(ano, mes - 1, dia).toLocaleDateString('pt-BR', {
        weekday: 'long',
        day: 'numeric',
        month: 'long',
    });
}
