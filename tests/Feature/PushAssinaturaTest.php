<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
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

    private function assinatura(string $auth): array
    {
        return [
            'endpoint' => self::ENDPOINT,
            'keys' => ['p256dh' => 'chave-publica-do-aparelho', 'auth' => $auth],
        ];
    }
}
