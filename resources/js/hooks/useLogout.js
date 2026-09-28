import axios from 'axios';

export function useLogout() {
    return async function handleLogout() {
        try {
            await axios.post('/api/logout');
        } catch (error) {
            console.error('Erro ao fazer logout', error);
        } finally {
            window.location.href = '/app';
        }
    };
}
