// Guarda o horário que o visitante montou na vitrine enquanto ele faz login.
// sessionStorage sobrevive à ida e volta do login com Google na mesma aba.
// Pode falhar (aba anônima, armazenamento bloqueado): aí o cliente só escolhe de novo.

const CHAVE = 'agendamento-pendente';

export function salvarPendente(escolha) {
    try {
        sessionStorage.setItem(CHAVE, JSON.stringify(escolha));
    } catch {
        // sem armazenamento: segue sem guardar
    }
}

export function lerPendente() {
    try {
        const valor = sessionStorage.getItem(CHAVE);
        return valor ? JSON.parse(valor) : null;
    } catch {
        return null;
    }
}

export function limparPendente() {
    try {
        sessionStorage.removeItem(CHAVE);
    } catch {
        // nada a fazer
    }
}
