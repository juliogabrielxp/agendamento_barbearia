<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Application\UseCases\AutenticarComSenhaInput;
use App\Application\UseCases\AutenticarComSenhaUseCase;
use App\Domain\Exceptions\CredenciaisInvalidasException;
use App\Domain\TipoDeConta;
use App\Http\Controllers\Controller;
use App\Infrastructure\Persistence\Eloquent\UserModel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class BarbeariaLoginController extends Controller
{
    public function __construct(
        private readonly AutenticarComSenhaUseCase $autenticarComSenha
    ) {
    }

    public function login(Request $request): JsonResponse
    {
        $dados = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        try {
            $usuario = $this->autenticarComSenha->executar(new AutenticarComSenhaInput(
                email: $dados['email'],
                senha: $dados['password'],
                tipoDeConta: TipoDeConta::Barbearia,
            ));
        }  catch (CredenciaisInvalidasException $e) {
            return response()->json(['message' => $e->getMessage(), 'errors' => ['email' => [$e->getMessage()]]], 422);
        }
        Auth::login(UserModel::findOrFail($usuario['id']));
        $request->session()->regenerate();

        return response()->json(['message' => 'Login realizado com sucesso']);
    }
}
