<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Feature\Concerns\CriaCenarioDeAgenda;
use Tests\TestCase;

/**
 * Regras que o código já garante e que agora o banco também recusa (BD-13, migrations M1 a M4),
 * para um script, um comando artisan ou um bug futuro não gravarem dado impossível.
 *
 * Só no MySQL (composer test:mysql): as CHECK constraints não são criadas no SQLite.
 */
class ConstraintsDoBancoTest extends TestCase
{
    use CriaCenarioDeAgenda;
    use RefreshDatabase;

    private const CHECK_VIOLADA = 3819;

    private const COLUNA_NAO_PODE_SER_NULA = 1048;

    private const FK_IMPEDE_APAGAR = 1451;

    protected function setUp(): void
    {
        parent::setUp();

        if (DB::connection()->getDriverName() !== 'mysql') {
            $this->markTestSkipped('CHECK constraints só existem no MySQL (composer test:mysql).');
        }

        // Um registro válido de cada tabela; cada caso tenta estragar um deles
        $barbearia = $this->criarBarbearia('dona@estudio.com');
        ['cliente' => $cliente] = $this->criarCliente('maria@email.com');
        $this->criarAgendamento($barbearia, $cliente, '2030-01-10 10:00:00');
    }

    /** @return array<string, array{string, int}> */
    public static function gravacoesInvalidas(): array
    {
        return [
            // M1: agendamentos
            'C1 status fora da lista' => ["UPDATE agendamentos SET status = 'pendente'", self::CHECK_VIOLADA],
            'agendamento com duração zero' => ['UPDATE agendamentos SET duracao_em_minutos = 0', self::CHECK_VIOLADA],
            'agendamento com preço negativo' => ['UPDATE agendamentos SET preco = -1', self::CHECK_VIOLADA],
            // M2: serviços e profissionais
            'C2 serviço com duração zero' => ['UPDATE servicos SET duracao_em_minutos = 0', self::CHECK_VIOLADA],
            'C3 serviço com preço negativo' => ['UPDATE servicos SET preco = -0.01', self::CHECK_VIOLADA],
            'C6 serviço sem nome' => ["UPDATE servicos SET nome = '   '", self::CHECK_VIOLADA],
            'C6 profissional sem nome' => ["UPDATE profissionais SET nome = ''", self::CHECK_VIOLADA],
            // M3: expediente e domínio
            'C4 fecha antes de abrir' => ["UPDATE barbearias SET hora_fechamento = '08:00:00'", self::CHECK_VIOLADA],
            'C5 intervalo só com início' => ["UPDATE barbearias SET intervalo_inicio = '12:00:00', intervalo_fim = NULL", self::CHECK_VIOLADA],
            'C5 intervalo só com fim' => ["UPDATE barbearias SET intervalo_inicio = NULL, intervalo_fim = '13:00:00'", self::CHECK_VIOLADA],
            'C5 intervalo antes da abertura' => ["UPDATE barbearias SET intervalo_inicio = '07:00:00', intervalo_fim = '09:00:00'", self::CHECK_VIOLADA],
            'C5 intervalo depois do fechamento' => ["UPDATE barbearias SET intervalo_inicio = '18:00:00', intervalo_fim = '20:00:00'", self::CHECK_VIOLADA],
            'C5 intervalo invertido' => ["UPDATE barbearias SET intervalo_inicio = '13:00:00', intervalo_fim = '12:00:00'", self::CHECK_VIOLADA],
            'C6 estabelecimento sem nome' => ["UPDATE barbearias SET nome = ''", self::CHECK_VIOLADA],
            'C8 domínio com maiúscula' => ["UPDATE barbearias SET dominio = 'Trancas.test'", self::CHECK_VIOLADA],
            'C8 domínio com espaço' => ["UPDATE barbearias SET dominio = 'tran cas.test'", self::CHECK_VIOLADA],
            // M4: contas
            'C6 cliente sem nome' => ["UPDATE clientes SET nome = ''", self::CHECK_VIOLADA],
            'C6 usuário sem nome' => ["UPDATE users SET nome = ''", self::CHECK_VIOLADA],
            'C7 conta sem senha e sem Google' => ['UPDATE users SET password = NULL, google_id = NULL', self::CHECK_VIOLADA],
            'N2 cliente sem conta' => ['UPDATE clientes SET user_id = NULL', self::COLUNA_NAO_PODE_SER_NULA],
            'N2 apagar conta que tem cliente' => ['DELETE FROM users WHERE id IN (SELECT user_id FROM clientes)', self::FK_IMPEDE_APAGAR],
        ];
    }

    #[DataProvider('gravacoesInvalidas')]
    public function test_banco_recusa(string $sql, int $erroEsperado): void
    {
        try {
            DB::statement($sql);
        } catch (QueryException $e) {
            $this->assertSame($erroEsperado, $e->errorInfo[1] ?? null, $e->getMessage());

            return;
        }

        $this->fail("O banco aceitou: {$sql}");
    }

    public function test_banco_aceita_o_que_e_valido(): void
    {
        DB::statement("UPDATE barbearias SET intervalo_inicio = '12:00:00', intervalo_fim = '13:00:00', dominio = 'trancas.juliogabrieldev.com'");
        DB::statement('UPDATE barbearias SET intervalo_inicio = NULL, intervalo_fim = NULL');
        DB::statement("UPDATE agendamentos SET status = 'cancelado'");
        DB::statement("UPDATE users SET google_id = CONCAT('google-', id), password = NULL");
        DB::statement('UPDATE servicos SET preco = 0');

        $this->assertTrue(true);
    }
}
