export default function Login() {
    function entrarComoCliente() {
        window.location.href = '/auth/google/cliente/redirect';
    }

    return (
        <div className="min-h-screen flex items-center justify-center bg-neutral-50 px-4">
            <div className="bg-white border border-neutral-200 rounded-xl p-8 w-full max-w-sm text-center">
                <h1 className="text-lg font-semibold mb-6 text-neutral-900">
                    Agendamento Barbearia
                </h1>

                <button
                    onClick={entrarComoCliente}
                    className="w-full bg-neutral-900 text-white py-3 rounded-lg text-sm font-medium hover:bg-neutral-700 transition"
                >
                    Entrar com Google
                </button>
            </div>
        </div>
    );
}
