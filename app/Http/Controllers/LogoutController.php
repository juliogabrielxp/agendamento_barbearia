<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LogoutController extends Controller
{
    public function logout(Request $request)
    {
        Auth::guard('web')->logout();
        // O auth:sanctum deixou o guard "sanctum" como padrão, com o usuário em memória, e é
        // dele que a sessão no banco tira o user_id ao gravar a sessão nova no fim da resposta
        Auth::guard('sanctum')->forgetUser();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['message' => 'Logout realizado com sucesso']);
    }
}
