<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Application\UseCases\AutenticarComGoogleInput;
use App\Application\UseCases\AutenticarComGoogleUseCase;
use App\Domain\Exceptions\EmailJaCadastradoException;
use App\Http\Controllers\Controller;
use App\Infrastructure\Persistence\Eloquent\ClienteModel;
use App\Infrastructure\Persistence\Eloquent\UserModel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

final class GoogleAuthController extends Controller
{
    public function __construct(
        private readonly AutenticarComGoogleUseCase $useCase
    ) {}

    public function redirectCliente(): RedirectResponse
    {
        return $this->driver()->redirect();
    }

    public function callbackCliente(Request $request): RedirectResponse
    {
        $googleUser = $this->driver()->user();

        try {
            $dadosUser = $this->autenticar($googleUser);
        } catch (EmailJaCadastradoException) {
            return redirect('/app/entrar?erro=google-email-em-uso');
        }

        $cliente = ClienteModel::firstOrCreate(
            ['user_id' => $dadosUser['id']],
            [
                'nome' => $dadosUser['nome'],
                'email' => $dadosUser['email'],
                'telefone' => '',
            ]
        );

        Auth::login(UserModel::find($dadosUser['id']));
        $request->session()->regenerate();

        // A vitrine retoma o horário que o cliente montou antes de entrar
        return redirect('/app');
    }

    private function driver()
    {
        // route() usa o host da requisição: cada estabelecimento volta para o próprio domínio
        return Socialite::driver('google')->redirectUrl(route('auth.google.cliente.callback'));
    }

    private function autenticar($googleUser): array
    {
        $input = new AutenticarComGoogleInput(
            googleId: $googleUser->getId(),
            nome: $googleUser->getName(),
            email: $googleUser->getEmail(),
            avatar: $googleUser->getAvatar(),
            emailVerificado: ($googleUser->getRaw()['email_verified'] ?? false) === true
        );

        return $this->useCase->executar($input);
    }
}
