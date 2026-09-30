<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Infrastructure\Persistence\Eloquent\BarbeariaModel;
use App\Infrastructure\Persistence\Eloquent\UserModel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class BarbeariaLoginController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $dados = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = UserModel::where('email', $dados['email'])->first();

        $credenciaisValidas = $user
            && $user->password
            && Hash::check($dados['password'], $user->password)
            && BarbeariaModel::where('user_id', $user->id)->exists();

        if (!$credenciaisValidas) {
            throw ValidationException::withMessages([
                'email' => ['E-mail ou senha inválidos.'],
            ]);
        }

        Auth::login($user);
        $request->session()->regenerate();

        return response()->json(['message' => 'Login realizado com sucesso']);
    }
}
