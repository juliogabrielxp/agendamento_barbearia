<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Infrastructure\Persistence\Eloquent\PushSubscriptionModel;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Concerns\CriaCenarioDeAgenda;
use Tests\TestCase;

class PushAssinaturaTest extends TestCase
{
    use CriaCenarioDeAgenda;
    use RefreshDatabase;

    private const ENDPOINT = 'https://push.exemplo.com/envio/aparelho-da-dona';

    public function test_dona_registra_a_assinatura_do_aparelho(): void
    {
        $minha = $this->criarBarbearia('dona@estudio.com');

        $this->actingAs($minha['dono'])
            ->postJson('/api/push/assinaturas', $this->assinatura('segredo-do-aparelho'))
            ->assertCreated();

        $this->assertDatabaseHas('push_subscriptions', [
            'endpoint_hash' => hash('sha256', self::ENDPOINT),
            'user_id' => $minha['dono']->id,
        ]);
    }

    public function test_outra_conta_nao_toma_a_assinatura_so_com_o_endpoint(): void
    {
        $minha = $this->criarBarbearia('dona@estudio.com');
        ['usuario' => $invasor] = $this->criarCliente('invasor@email.com');

        $this->actingAs($minha['dono'])
            ->postJson('/api/push/assinaturas', $this->assinatura('segredo-do-aparelho'))
            ->assertCreated();

        $this->actingAs($invasor)
            ->postJson('/api/push/assinaturas', $this->assinatura('segredo-inventado'))
            ->assertStatus(409);

        $this->assertDatabaseHas('push_subscriptions', [
            'endpoint_hash' => hash('sha256', self::ENDPOINT),
            'user_id' => $minha['dono']->id,
            'auth' => 'segredo-do-aparelho',
        ]);
    }

    public function test_o_mesmo_aparelho_passa_para_outra_conta_com_a_mesma_assinatura(): void
    {
        $minha = $this->criarBarbearia('dona@estudio.com');
        ['usuario' => $outraConta] = $this->criarCliente('outra@email.com');

        $this->actingAs($minha['dono'])
            ->postJson('/api/push/assinaturas', $this->assinatura('segredo-do-aparelho'))
            ->assertCreated();

        $this->actingAs($outraConta)
            ->postJson('/api/push/assinaturas', $this->assinatura('segredo-do-aparelho'))
            ->assertCreated();

        $this->assertDatabaseHas('push_subscriptions', [
            'endpoint_hash' => hash('sha256', self::ENDPOINT),
            'user_id' => $outraConta->id,
        ]);
    }

    public function test_outra_conta_nao_toma_a_assinatura_gravada_no_meio_da_requisicao(): void
    {
        $minha = $this->criarBarbearia('dona@estudio.com');
        ['usuario' => $invasor] = $this->criarCliente('invasor@email.com');

        // A dona registra o aparelho logo depois de a requisição do invasor ver que ele não existia
        $this->gravarNoMeio($minha['dono']->id, 'segredo-do-aparelho');

        $this->actingAs($invasor)
            ->postJson('/api/push/assinaturas', $this->assinatura('segredo-inventado'))
            ->assertStatus(409);

        $this->assertDatabaseHas('push_subscriptions', [
            'endpoint_hash' => hash('sha256', self::ENDPOINT),
            'user_id' => $minha['dono']->id,
            'auth' => 'segredo-do-aparelho',
        ]);
    }

    public function test_clique_duplo_da_mesma_conta_grava_uma_assinatura_so(): void
    {
        $minha = $this->criarBarbearia('dona@estudio.com');

        $this->gravarNoMeio($minha['dono']->id, 'segredo-do-aparelho');

        $this->actingAs($minha['dono'])
            ->postJson('/api/push/assinaturas', $this->assinatura('segredo-do-aparelho'))
            ->assertCreated();

        $this->assertSame(1, PushSubscriptionModel::count());
    }

    /** Simula outra requisição gravando o mesmo aparelho logo depois da primeira leitura */
    private function gravarNoMeio(int $userId, string $auth): void
    {
        $jaGravou = false;
        DB::listen(function (QueryExecuted $query) use (&$jaGravou, $userId, $auth): void {
            $sql = str_replace(['`', '"'], '', $query->sql);
            if ($jaGravou || ! str_starts_with($sql, 'select * from push_subscriptions where endpoint_hash')) {
                return;
            }
            $jaGravou = true;
            PushSubscriptionModel::create([
                'user_id' => $userId,
                'endpoint' => self::ENDPOINT,
                'endpoint_hash' => hash('sha256', self::ENDPOINT),
                'p256dh' => 'chave-publica-do-aparelho',
                'auth' => $auth,
            ]);
        });
    }

    private function assinatura(string $auth): array
    {
        return [
            'endpoint' => self::ENDPOINT,
            'keys' => ['p256dh' => 'chave-publica-do-aparelho', 'auth' => $auth],
        ];
    }
}
