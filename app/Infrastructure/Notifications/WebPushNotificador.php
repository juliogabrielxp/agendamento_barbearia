<?php

declare(strict_types=1);

namespace App\Infrastructure\Notifications;

use GuzzleHttp\Client;
use App\Domain\Contracts\NotificadorDePushInterface;
use App\Infrastructure\Persistence\Eloquent\PushSubscriptionModel;
use Illuminate\Support\Facades\Log;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;

class WebPushNotificador implements NotificadorDePushInterface
{
    public function enviarParaUsuario(int $userId, string $titulo, string $mensagem, string $url): int
    {
        $assinaturas = PushSubscriptionModel::where('user_id', $userId)->get();

        if ($assinaturas->isEmpty()) {
            return 0;
        }

        $webPush = new WebPush(
        [
        'VAPID' => [
            'subject' => config('services.webpush.subject'),
            'publicKey' => config('services.webpush.public_key'),
            'privateKey' => config('services.webpush.private_key'),
        ],
        ],
        [],
        new Client(['timeout' => 3])
        );

        $conteudo = json_encode(
            ['titulo' => $titulo, 'mensagem' => $mensagem, 'url' => $url],
            JSON_UNESCAPED_UNICODE
        );

        foreach ($assinaturas as $assinatura) {
            $webPush->queueNotification(
                Subscription::create([
                    'endpoint' => $assinatura->endpoint,
                    'publicKey' => $assinatura->p256dh,
                    'authToken' => $assinatura->auth,
                ]),
                $conteudo
            );
        }

        $entregues = 0;

        foreach ($webPush->flush() as $relatorio) {
            if ($relatorio->isSuccess()) {
                $entregues++;
                continue;
            }

            // Aparelho desinstalou o app ou revogou a permissão: remove a assinatura
            if ($relatorio->isSubscriptionExpired()) {
                PushSubscriptionModel::where('endpoint_hash', hash('sha256', $relatorio->getEndpoint()))->delete();
                continue;
            }

            Log::warning('Falha ao enviar push', ['motivo' => $relatorio->getReason()]);
        }

        return $entregues;
    }
}
