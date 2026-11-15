import React from 'react';
import { createRoot } from 'react-dom/client';
import { BrowserRouter, Navigate, Routes, Route } from 'react-router-dom';
import Vitrine from './pages/Vitrine';
import Login from './pages/Login';
import DashboardEstabelecimento from './pages/DashboardEstabelecimento';
import DashboardCliente from './pages/DashboardCliente';
import LoginEstabelecimento from './pages/LoginEstabelecimento';
import '../css/app.css';

function App() {
    return (
        <BrowserRouter>
            <Routes>
                <Route path="/app" element={<Vitrine />} />
                <Route path="/app/entrar" element={<Login />} />
                <Route path="/app/minha-conta" element={<DashboardCliente />} />
                <Route path="/app/painel" element={<DashboardEstabelecimento />} />
                <Route path="/app/painel/entrar" element={<LoginEstabelecimento />} />

                {/* Endereços antigos (links salvos, notificações já enviadas) */}
                <Route path="/app/dashboard-cliente" element={<Navigate to="/app/minha-conta" replace />} />
                <Route path="*" element={<Navigate to="/app" replace />} />
            </Routes>
        </BrowserRouter>
    );
}

const root = createRoot(document.getElementById('app'));
root.render(<App />);

if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js').catch(erro => {
            console.warn('Service worker não registrado', erro);
        });
    });
}
