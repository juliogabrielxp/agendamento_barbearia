<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Infrastructure\Persistence\Eloquent\ClienteModel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MeController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();
        $estabelecimento = $this->estabelecimento($request);

        return response()->json([
            'user' => [
                'id' => $user->id,
                'nome' => $user->nome,
                'email' => $user->email,
                'avatar' => $user->avatar,
            ],
            // Só é dona aqui se o host for o estabelecimento dela; em outro endereço ela navega como cliente
            'estabelecimento' => (int) $estabelecimento->user_id === (int) $user->id ? $estabelecimento : null,
            'cliente' => ClienteModel::where('user_id', $user->id)->first(),
        ]);
    }
}
