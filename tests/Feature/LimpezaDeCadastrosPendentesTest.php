<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Infrastructure\Persistence\Eloquent\CadastroPendenteModel;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Cadastro que ninguém confirmou guarda nome, e-mail e hash de senha: um dia depois
 * de expirar, ele é apagado pelo model:prune agendado.
 */
class LimpezaDeCadastrosPendentesTest extends TestCase
{
    use RefreshDatabase;

    public function test_prune_apaga_o_expirado_ha_mais_de_um_dia_e_mantem_os_outros(): void
    {
        $this->travelTo('2030-01-10 12:00:00');
        $this->criarPendente('antigo@email.com', expiraEm: '2030-01-09 11:59:00');
        $this->criarPendente('recente@email.com', expiraEm: '2030-01-10 11:00:00');
        $this->criarPendente('valido@email.com', expiraEm: '2030-01-10 12:15:00');

        $this->artisan('model:prune', ['--model' => [CadastroPendenteModel::class]])->assertSuccessful();

        $this->assertSame(
            ['recente@email.com', 'valido@email.com'],
            CadastroPendenteModel::orderBy('email')->pluck('email')->all()
        );
    }

    public function test_limpeza_esta_agendada_com_o_model_certo(): void
    {
        $this->artisan('schedule:list')
            ->expectsOutputToContain('model:prune')
            ->assertSuccessful();

        $comando = collect(app(Schedule::class)->events())
            ->map(fn ($evento) => $evento->command)
            ->first(fn (?string $comando) => $comando !== null && str_contains($comando, 'model:prune'));

        $this->assertNotNull($comando);
        $this->assertStringContainsString('CadastroPendenteModel', $comando);
    }

    private function criarPendente(string $email, string $expiraEm): void
    {
        CadastroPendenteModel::create([
            'email' => $email,
            'nome' => 'Fulana',
            'senha_hash' => 'hash',
            'codigo_hash' => 'hash',
            'chave_hash' => str_repeat('a', 64),
            'expira_em' => $expiraEm,
            'enviado_em' => '2030-01-09 00:00:00',
        ]);
    }
}
