import React from 'react';
import { createRoot } from 'react-dom/client';
import { BrowserRouter, Routes, Route } from 'react-router-dom';
import Login from './pages/Login';
import DashboardBarbearia from './pages/DashboardBarbearia';
import DashboardCliente from './pages/DashboardCliente';
import '../css/app.css';

function App() {
    return (
        <BrowserRouter>
            <Routes>
                <Route path="/app" element={<Login />} />
                <Route path="/app/dashboard-barbearia" element={<DashboardBarbearia />} />
                <Route path="/app/dashboard-cliente" element={<DashboardCliente />} />
            </Routes>
        </BrowserRouter>
    );
}

const root = createRoot(document.getElementById('app'));
root.render(<App />);
