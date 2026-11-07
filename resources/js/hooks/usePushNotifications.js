import { useCallback, useEffect, useState } from 'react';
import api from '../lib/api';

function urlBase64ParaUint8Array(base64) {
    const preenchimento = '='.repeat((4 - (base64.length % 4)) % 4);
    const normalizado = (base64 + preenchimento).replace(/-/g, '+').replace(/_/g, '/');
    return Uint8Array.from(atob(normalizado), caractere => caractere.charCodeAt(0));
}

const suportado = () =>
    'serviceWorker' in navigator && 'PushManager' in window && 'Notification' in window;

// estado: 'carregando' | 'nao-suportado' | 'bloqueado' | 'inativo' | 'ativo'
export function usePushNotifications() {
    const [estado, setEstado] = useState('carregando');
    const [erro, setErro] = useState(null);

    const atualizarEstado = useCallback(async () => {
        if (!suportado()) {
            setEstado('nao-suportado');
            return;
        }

        if (Notification.permission === 'denied') {
            setEstado('bloqueado');
            return;
        }

        const registro = await navigator.serviceWorker.getRegistration();
        const assinatura = await registro?.pushManager.getSubscription();
        setEstado(assinatura ? 'ativo' : 'inativo');
    }, []);

    useEffect(() => {
        atualizarEstado();
    }, [atualizarEstado]);

    const ativar = useCallback(async () => {
        setErro(null);
        let assinatura = null;

        try {
            const permissao = await Notification.requestPermission();

            if (permissao !== 'granted') {
                await atualizarEstado();
                return;
            }

            const registro = await navigator.serviceWorker.ready;
            const { data } = await api.get('/api/push/chave-publica');

            // Assinatura antiga (por exemplo, de outra chave VAPID) precisa sair antes
            const existente = await registro.pushManager.getSubscription();
            if (existente) await existente.unsubscribe();

            assinatura = await registro.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: urlBase64ParaUint8Array(data.chave),
            });

            await api.post('/api/push/assinaturas', assinatura.toJSON());
            setEstado('ativo');
        } catch {
            if (assinatura) await assinatura.unsubscribe().catch(() => {});
            setErro('Não foi possível ativar as notificações.');
            await atualizarEstado();
        }
    }, [atualizarEstado]);

    const desativar = useCallback(async () => {
        setErro(null);

        try {
            const registro = await navigator.serviceWorker.getRegistration();
            const assinatura = await registro?.pushManager.getSubscription();

            if (assinatura) {
                await api.delete('/api/push/assinaturas', { data: { endpoint: assinatura.endpoint } });
                await assinatura.unsubscribe();
            }

            setEstado('inativo');
        } catch {
            setErro('Não foi possível desativar as notificações.');
        }
    }, []);

    return { estado, erro, ativar, desativar };
}
