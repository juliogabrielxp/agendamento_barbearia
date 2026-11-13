<?php

namespace App\Providers;

use App\Domain\Contracts\AgendamentoRepositoryInterface;
use App\Domain\Contracts\BarbeariaRepositoryInterface;
use App\Domain\Contracts\CadastroPendenteRepositoryInterface;
use App\Domain\Contracts\ClienteRepositoryInterface;
use App\Domain\Contracts\EnviadorDeCodigoDeConfirmacaoInterface;
use App\Domain\Contracts\NotificadorDePushInterface;
use App\Domain\Contracts\PasswordHasherInterface;
use App\Domain\Contracts\RelogioInterface;
use App\Domain\Contracts\TransacaoInterface;
use App\Domain\Contracts\UserRepositoryInterface;
use App\Infrastructure\LaravelRelogio;
use App\Infrastructure\Notifications\EmailDeCodigoDeConfirmacao;
use App\Infrastructure\Notifications\WebPushNotificador;
use App\Infrastructure\Persistence\EloquentAgendamentoRepository;
use App\Infrastructure\Persistence\EloquentBarbeariaRepository;
use App\Infrastructure\Persistence\EloquentCadastroPendenteRepository;
use App\Infrastructure\Persistence\EloquentClienteRepository;
use App\Infrastructure\Persistence\EloquentUserRepository;
use App\Infrastructure\Persistence\LaravelTransacao;
use App\Infrastructure\Security\LaravelPasswordHasher;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(AgendamentoRepositoryInterface::class, EloquentAgendamentoRepository::class);
        $this->app->bind(UserRepositoryInterface::class, EloquentUserRepository::class);
        $this->app->bind(ClienteRepositoryInterface::class, EloquentClienteRepository::class);
        $this->app->bind(BarbeariaRepositoryInterface::class, EloquentBarbeariaRepository::class);
        $this->app->bind(PasswordHasherInterface::class, LaravelPasswordHasher::class);
        $this->app->bind(TransacaoInterface::class, LaravelTransacao::class);
        $this->app->bind(RelogioInterface::class, LaravelRelogio::class);
        $this->app->bind(NotificadorDePushInterface::class, WebPushNotificador::class);
        $this->app->bind(CadastroPendenteRepositoryInterface::class, EloquentCadastroPendenteRepository::class);
        $this->app->bind(EnviadorDeCodigoDeConfirmacaoInterface::class, EmailDeCodigoDeConfirmacao::class);
    }

    public function boot(): void
    {
        //
    }
}
