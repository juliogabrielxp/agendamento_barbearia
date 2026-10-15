<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Application\UseCases\AutenticarComSenhaInput;
use App\Application\UseCases\AutenticarComSenhaUseCase;
use App\Application\UseCases\CadastrarClienteInput;
use App\Application\UseCases\CadastrarClienteUseCase;
use App\Domain\Exceptions\CredenciaisInvalidasException;
use App\Domain\Exceptions\EmailJaCadastradoException;
use App\Domain\TipoDeConta;
use App\Http\Controllers\Controller;
use App\Infrastructure\Persistence\Eloquent\UserModel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ClienteAuthController extends Controller
{
    public function __construct(
        private readonly CadastrarClienteUseCase $cadastrarCliente,
        private readonly AutenticarComSenhaUseCase $autenticarComSenha
    ) {
    }

    public function cadastrar(Request $request): JsonResponse
    {
        $dados = $request->validate([
            'nome' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'password' => 'required|string|min:8|confirmed',
        ]);

        try {
            $usuario = $this->cadastrarCliente->executar(new CadastrarClienteInput(
                nome: $dados['nome'],
                email: $dados['email'],
                senha: $dados['password'],
            ));
        } catch (EmailJaCadastradoException $e) {
            return response()->json(['message' => $e->getMessage(), 'errors' => ['email' => [$e->getMessage()]]], 422);
        }

        $this->abrirSessao($request, $usuario['id']);

        return response()->json(['message' => 'Conta criada com sucesso'], 201);
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
                tipoDeConta: TipoDeConta::Cliente,
            ));
        } catch (CredenciaisInvalidasException $e) {
            return response()->json(['message' => $e->getMessage(), 'errors' => ['email' => [$e->getMessage()]]], 422);
        }

        $this->abrirSessao($request, $usuario['id']);

        return response()->json(['message' => 'Login realizado com sucesso']);
    }

    private function abrirSessao(Request $request, int $userId): void
    {
        Auth::login(UserModel::findOrFail($userId));
        $request->session()->regenerate();
    }
}
