<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Application\UseCases\AutenticarComGoogleUseCase;
use App\Application\UseCases\AutenticarComGoogleInput;
use App\Http\Controllers\Controller;
use App\Infrastructure\Persistence\Eloquent\BarbeariaModel;
use App\Infrastructure\Persistence\Eloquent\ClienteModel;
use App\Infrastructure\Persistence\Eloquent\UserModel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class GoogleAuthController extends Controller
{
    public function __construct(
        private readonly AutenticarComGoogleUseCase $useCase
    ) {
    }

    public function redirectBarbearia(): RedirectResponse
    {
        return $this->driver('barbearia')->redirect();
    }

    public function redirectCliente(): RedirectResponse
    {
        return $this->driver('cliente')->redirect();
    }

    public function callbackBarbearia(): RedirectResponse
    {
        $googleUser = $this->driver('barbearia')->user();
        $dadosUser = $this->autenticar($googleUser);

        $barbearia = BarbeariaModel::firstOrCreate(
            ['user_id' => $dadosUser['id']],
            [
                'nome' => $dadosUser['nome'],
                'email' => $dadosUser['email'],
                'telefone' => '',
                'endereco' => '',
            ]
        );

        Auth::login(UserModel::find($dadosUser['id']));

        return redirect('/app/dashboard-barbearia');
    }

    public function callbackCliente(): RedirectResponse
    {
        $googleUser = $this->driver('cliente')->user();
        $dadosUser = $this->autenticar($googleUser);

        $cliente = ClienteModel::firstOrCreate(
            ['user_id' => $dadosUser['id']],
            [
                'nome' => $dadosUser['nome'],
                'email' => $dadosUser['email'],
                'telefone' => '',
            ]
        );

        Auth::login(UserModel::find($dadosUser['id']));

        return redirect('/app/dashboard-cliente');
    }

    private function driver(string $tipo)
    {
        return Socialite::driver('google')->redirectUrl(
            route("auth.google.{$tipo}.callback")
        );
    }

    private function autenticar($googleUser): array
    {
        $input = new AutenticarComGoogleInput(
            googleId: $googleUser->getId(),
            nome: $googleUser->getName(),
            email: $googleUser->getEmail(),
            avatar: $googleUser->getAvatar()
        );

        return $this->useCase->executar($input);
    }
}
