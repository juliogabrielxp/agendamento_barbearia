<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Infrastructure\Persistence\Eloquent\ClienteModel;
use App\Infrastructure\Persistence\Eloquent\UserModel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class ClienteAuthController extends Controller
{
    public function cadastrar(Request $request): JsonResponse
    {
        $dados = $request->validate([
            'nome' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
        ], [
            'email.unique' => 'Este e-mail já está cadastrado. Faça login.',
        ]);

        $user = DB::transaction(function () use ($dados) {
            $user = UserModel::create([
                'nome' => $dados['nome'],
                'email' => $dados['email'],
                'password' => $dados['password'],
            ]);

            ClienteModel::create([
                'user_id' => $user->id,
                'nome' => $dados['nome'],
                'email' => $dados['email'],
                'telefone' => '',
            ]);

            return $user;
        });

        Auth::login($user);
        $request->session()->regenerate();

        return response()->json(['message' => 'Conta criada com sucesso'], 201);
    }

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
            && ClienteModel::where('user_id', $user->id)->exists();

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
