<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Infrastructure\Persistence\Eloquent\PushSubscriptionModel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PushAssinaturaController extends Controller
{
    public function chavePublica(): JsonResponse
    {
        return response()->json(['chave' => config('services.webpush.public_key')]);
    }

    public function store(Request $request): JsonResponse
    {
        $dados = $request->validate([
            'endpoint' => 'required|url|max:2000',
            'keys.p256dh' => 'required|string|max:255',
            'keys.auth' => 'required|string|max:255',
        ]);

        $endpointHash = hash('sha256', $dados['endpoint']);
        $valores = [
            'user_id' => $request->user()->id,
            'endpoint' => $dados['endpoint'],
            'p256dh' => $dados['keys']['p256dh'],
            'auth' => $dados['keys']['auth'],
        ];

        $existente = PushSubscriptionModel::where('endpoint_hash', $endpointHash)->first();

        if ($existente === null) {
            // Se outra requisição gravar o mesmo aparelho entre a leitura e o insert, o unique
            // recusa e a linha dela volta aqui: passa pela mesma conferência abaixo
            $existente = PushSubscriptionModel::createOrFirst(['endpoint_hash' => $endpointHash], $valores);

            if ($existente->wasRecentlyCreated) {
                return response()->json(null, 201);
            }
        }

        // O aparelho só passa para outra conta se ela provar que tem a assinatura: o segredo
        // "auth" sai do navegador junto com o endpoint, e quem só conhece a URL não o tem.
        if ((int) $existente->user_id !== (int) $request->user()->id
            && ! hash_equals((string) $existente->auth, $dados['keys']['auth'])) {
            return response()->json(['message' => 'Assinatura de notificação inválida.'], 409);
        }

        $existente->update($valores);

        return response()->json(null, 201);
    }

    public function destroy(Request $request): JsonResponse
    {
        $request->validate(['endpoint' => 'required|url']);

        PushSubscriptionModel::where('user_id', $request->user()->id)
            ->where('endpoint_hash', hash('sha256', $request->input('endpoint')))
            ->delete();

        return response()->json(null, 204);
    }
}
