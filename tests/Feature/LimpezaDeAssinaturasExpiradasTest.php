<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Infrastructure\Notifications\WebPushNotificador;
use App\Infrastructure\Persistence\Eloquent\PushSubscriptionModel;
use App\Infrastructure\Persistence\Eloquent\UserModel;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Promise\Create;
use GuzzleHttp\Psr7\Response;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Minishlink\WebPush\VAPID;
use Psr\Http\Message\RequestInterface;
use Tests\TestCase;

/**
 * Quando o serviço de push responde 410 (o aparelho desinstalou o app ou revogou a
 * permissão), a assinatura é apagada. Várias expiradas saem com um DELETE só.
 */
class LimpezaDeAssinaturasExpiradasTest extends TestCase
{
    use RefreshDatabase;

    public function test_apaga_as_expiradas_num_delete_so_e_mantem_a_que_recebeu(): void
    {
        $vapid = VAPID::createVapidKeys();
        config(['services.webpush' => [
            'public_key' => $vapid['publicKey'],
            'private_key' => $vapid['privateKey'],
            'subject' => 'mailto:teste@exemplo.com',
        ]]);

        $usuario = UserModel::create(['nome' => 'Maria', 'email' => 'maria@email.com', 'password' => 'senha-segura-123']);
        foreach (['expirada-1', 'expirada-2', 'ativa'] as $nome) {
            $this->criarAssinatura($usuario->id, "https://push.exemplo.com/{$nome}");
        }

        $deletes = 0;
        DB::listen(function (QueryExecuted $query) use (&$deletes): void {
            if (str_starts_with(str_replace(['`', '"'], '', $query->sql), 'delete from push_subscriptions')) {
                $deletes++;
            }
        });

        $entregues = $this->notificadorQueResponde()->enviarParaUsuario($usuario->id, 'Título', 'Mensagem', '/app');

        $this->assertSame(1, $entregues);
        $this->assertSame(1, $deletes);
        $this->assertSame(
            ['https://push.exemplo.com/ativa'],
            PushSubscriptionModel::where('user_id', $usuario->id)->pluck('endpoint')->all()
        );
    }

    /** Serviço de push falso: 410 para os endpoints "expirada-*", 201 para os outros */
    private function notificadorQueResponde(): WebPushNotificador
    {
        return new class extends WebPushNotificador
        {
            protected function clienteHttp(): Client
            {
                return new Client(['handler' => HandlerStack::create(
                    fn (RequestInterface $request) => Create::promiseFor(
                        new Response(str_contains($request->getUri()->getPath(), 'expirada') ? 410 : 201)
                    )
                )]);
            }
        };
    }

    private function criarAssinatura(int $userId, string $endpoint): void
    {
        // Chave pública P-256 de verdade: o WebPush criptografa o conteúdo com ela
        $chave = openssl_pkey_get_details(openssl_pkey_new([
            'private_key_type' => OPENSSL_KEYTYPE_EC,
            'curve_name' => 'prime256v1',
        ]));
        $base64url = fn (string $bytes) => rtrim(strtr(base64_encode($bytes), '+/', '-_'), '=');

        PushSubscriptionModel::create([
            'user_id' => $userId,
            'endpoint_hash' => hash('sha256', $endpoint),
            'endpoint' => $endpoint,
            'p256dh' => $base64url("\x04".$chave['ec']['x'].$chave['ec']['y']),
            'auth' => $base64url(random_bytes(16)),
        ]);
    }
}
