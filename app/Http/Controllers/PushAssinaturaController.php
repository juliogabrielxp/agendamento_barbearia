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

        // Se o aparelho já estava ligado a outra conta, passa a ser da conta atual
        PushSubscriptionModel::updateOrCreate(
            ['endpoint_hash' => hash('sha256', $dados['endpoint'])],
            [
                'user_id' => $request->user()->id,
                'endpoint' => $dados['endpoint'],
                'p256dh' => $dados['keys']['p256dh'],
                'auth' => $dados['keys']['auth'],
            ]
        );

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
