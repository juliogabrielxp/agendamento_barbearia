// Service worker do app de agendamento.
// Por enquanto cuida só das notificações push. Não há cache offline de propósito:
// o app depende de sessão e de API, e cache de dados autenticados gera bugs difíceis.

self.addEventListener('install', () => {
    self.skipWaiting();
});

self.addEventListener('activate', event => {
    event.waitUntil(self.clients.claim());
});

self.addEventListener('push', event => {
    let dados = {};

    try {
        dados = event.data ? event.data.json() : {};
    } catch {
        dados = { mensagem: event.data ? event.data.text() : '' };
    }

    const titulo = dados.titulo || 'Novo agendamento';

    event.waitUntil(
        self.registration.showNotification(titulo, {
            body: dados.mensagem || '',
            icon: '/icons/icon-192.png',
            data: { url: dados.url || '/app/dashboard-barbearia' },
        })
    );
});

self.addEventListener('notificationclick', event => {
    event.notification.close();

    const destino = new URL(
        event.notification.data?.url || '/app/dashboard-barbearia',
        self.location.origin
    ).href;

    event.waitUntil(
        (async () => {
            const janelas = await self.clients.matchAll({ type: 'window', includeUncontrolled: true });

            for (const janela of janelas) {
                if (new URL(janela.url).origin === self.location.origin) {
                    await janela.focus();

                    try {
                        await janela.navigate(destino);
                    } catch {
                        // se não der pra navegar, a janela já foi trazida pra frente
                    }

                    return;
                }
            }

            await self.clients.openWindow(destino);
        })()
    );
});
