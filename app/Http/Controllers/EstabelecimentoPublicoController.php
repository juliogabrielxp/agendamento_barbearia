<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Infrastructure\Persistence\Eloquent\ProfissionalModel;
use App\Infrastructure\Persistence\Eloquent\ServicoModel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Vitrine pública do estabelecimento do host: aberta sem login, então
 * cada campo devolvido é escolhido a dedo (nada de user_id, e-mail da conta etc.).
 */
final class EstabelecimentoPublicoController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $estabelecimento = $this->estabelecimento($request);

        $profissionais = ProfissionalModel::where('barbearia_id', $estabelecimento->id)
            ->with('servicos')
            ->orderBy('id')
            ->get();

        // Serviço que ninguém realiza não pode ser agendado, então nem aparece
        $idsAtendidos = $profissionais->flatMap(fn (ProfissionalModel $p) => $p->servicos->pluck('id'))->unique();

        $servicos = ServicoModel::where('barbearia_id', $estabelecimento->id)
            ->whereIn('id', $idsAtendidos)
            ->orderBy('nome')
            ->get();

        $horario = fn (?string $valor) => $valor ? substr($valor, 0, 5) : null;

        return response()->json([
            'nome' => $estabelecimento->nome,
            'endereco' => $estabelecimento->endereco,
            'telefone' => $estabelecimento->telefone,
            'imagem_url' => $estabelecimento->imagem_url,
            'hora_abertura' => $horario($estabelecimento->hora_abertura),
            'hora_fechamento' => $horario($estabelecimento->hora_fechamento),
            'servicos' => $servicos->map(fn (ServicoModel $servico) => [
                'id' => $servico->id,
                'nome' => $servico->nome,
                'duracao_em_minutos' => $servico->duracao_em_minutos,
                'preco' => (float) $servico->preco,
                'imagem_url' => $servico->imagem_url,
            ])->values(),
            'profissionais' => $profissionais->map(fn (ProfissionalModel $profissional) => [
                'id' => $profissional->id,
                'nome' => $profissional->nome,
                'servicos' => $profissional->servicos->pluck('id')->values(),
            ])->values(),
        ]);
    }
}
