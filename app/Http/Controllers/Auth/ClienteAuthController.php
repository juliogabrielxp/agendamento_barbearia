<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Application\UseCases\AutenticarComSenhaInput;
use App\Application\UseCases\AutenticarComSenhaUseCase;
use App\Application\UseCases\ConfirmarCadastroDeClienteInput;
use App\Application\UseCases\ConfirmarCadastroDeClienteUseCase;
use App\Application\UseCases\ReenviarCodigoDeConfirmacaoInput;
use App\Application\UseCases\ReenviarCodigoDeConfirmacaoUseCase;
use App\Application\UseCases\SolicitarCadastroDeClienteInput;
use App\Application\UseCases\SolicitarCadastroDeClienteUseCase;
use App\Domain\Exceptions\ConfirmacaoDeEmailException;
use App\Domain\Exceptions\CredenciaisInvalidasException;
use App\Domain\Exceptions\EmailJaCadastradoException;
use App\Domain\Exceptions\EnvioDeEmailFalhouException;
use App\Domain\Exceptions\ReenvioDeCodigoMuitoCedoException;
use App\Domain\TipoDeConta;
use App\Http\Controllers\Controller;
use App\Infrastructure\Persistence\Eloquent\UserModel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ClienteAuthController extends Controller
{
    /** Guarda e-mail e chave do cadastro pendente; a chave nunca vai para o navegador */
    private const SESSAO_CADASTRO_PENDENTE = 'cadastro_pendente';

    public function __construct(
        private readonly SolicitarCadastroDeClienteUseCase $solicitarCadastro,
        private readonly ConfirmarCadastroDeClienteUseCase $confirmarCadastro,
        private readonly ReenviarCodigoDeConfirmacaoUseCase $reenviarCodigo,
        private readonly AutenticarComSenhaUseCase $autenticarComSenha
    ) {}

    /** Primeiro passo: manda o código por e-mail. A conta ainda não existe. */
    public function cadastrar(Request $request): JsonResponse
    {
        $dados = $request->validate([
            'nome' => 'required|string|max:255',
            'email' => 'required|email|max:255|confirmed',
            'password' => 'required|string|min:8|confirmed',
        ], [
            'email.confirmed' => 'Os e-mails não são iguais.',
        ]);

        try {
            $chave = $this->solicitarCadastro->executar(new SolicitarCadastroDeClienteInput(
                nome: $dados['nome'],
                email: $dados['email'],
                senha: $dados['password'],
                nomeDoEstabelecimento: $this->estabelecimento($request)->nome,
            ));
        } catch (EmailJaCadastradoException $e) {
            return $this->erro($e->getMessage(), 'email', 422);
        } catch (ReenvioDeCodigoMuitoCedoException $e) {
            return $this->erro($e->getMessage(), 'email', 429);
        } catch (EnvioDeEmailFalhouException $e) {
            return $this->erro($e->getMessage(), 'geral', 503);
        }

        $request->session()->put(self::SESSAO_CADASTRO_PENDENTE, [
            'email' => $dados['email'],
            'chave' => $chave,
        ]);

        return response()->json([
            'message' => 'Enviamos um código para o seu e-mail.',
            'email' => $dados['email'],
        ], 202);
    }

    /** Segundo passo: confere o código, cria a conta e já abre a sessão */
    public function confirmarCadastro(Request $request): JsonResponse
    {
        $dados = $request->validate([
            'codigo' => 'required|digits:6',
        ]);

        $pendente = $request->session()->get(self::SESSAO_CADASTRO_PENDENTE);

        if (! is_array($pendente)) {
            return $this->erro(ConfirmacaoDeEmailException::cadastroNaoEncontrado()->getMessage(), 'codigo', 422);
        }

        try {
            $usuario = $this->confirmarCadastro->executar(new ConfirmarCadastroDeClienteInput(
                email: $pendente['email'],
                chave: $pendente['chave'],
                codigo: $dados['codigo'],
            ));
        } catch (ConfirmacaoDeEmailException $e) {
            return $this->erro($e->getMessage(), 'codigo', 422);
        } catch (EmailJaCadastradoException $e) {
            $request->session()->forget(self::SESSAO_CADASTRO_PENDENTE);

            return $this->erro($e->getMessage(), 'email', 422);
        }

        $request->session()->forget(self::SESSAO_CADASTRO_PENDENTE);
        $this->abrirSessao($request, $usuario['id']);

        return response()->json(['message' => 'Conta criada com sucesso'], 201);
    }

    public function reenviarCodigo(Request $request): JsonResponse
    {
        $pendente = $request->session()->get(self::SESSAO_CADASTRO_PENDENTE);

        if (! is_array($pendente)) {
            return $this->erro(ConfirmacaoDeEmailException::cadastroNaoEncontrado()->getMessage(), 'codigo', 422);
        }

        try {
            $this->reenviarCodigo->executar(new ReenviarCodigoDeConfirmacaoInput(
                email: $pendente['email'],
                chave: $pendente['chave'],
                nomeDoEstabelecimento: $this->estabelecimento($request)->nome,
            ));
        } catch (ConfirmacaoDeEmailException $e) {
            return $this->erro($e->getMessage(), 'codigo', 422);
        } catch (ReenvioDeCodigoMuitoCedoException $e) {
            return $this->erro($e->getMessage(), 'codigo', 429);
        } catch (EnvioDeEmailFalhouException $e) {
            return $this->erro($e->getMessage(), 'geral', 503);
        }

        return response()->json(['message' => 'Enviamos um novo código para o seu e-mail.'], 202);
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
            return $this->erro($e->getMessage(), 'email', 422);
        }

        $this->abrirSessao($request, $usuario['id']);

        return response()->json(['message' => 'Login realizado com sucesso']);
    }

    private function abrirSessao(Request $request, int $userId): void
    {
        Auth::login(UserModel::findOrFail($userId));
        $request->session()->regenerate();
    }

    private function erro(string $mensagem, string $campo, int $status): JsonResponse
    {
        return response()->json(['message' => $mensagem, 'errors' => [$campo => [$mensagem]]], $status);
    }
}
