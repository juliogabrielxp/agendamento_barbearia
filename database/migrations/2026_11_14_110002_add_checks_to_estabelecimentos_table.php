<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * BD-13, M3: expediente e domínio do estabelecimento. Só no MySQL; ver M1.
 *
 * - C4: fecha depois de abrir.
 * - C5: intervalo com os dois horários ou nenhum (BD-32), dentro do expediente e com fim depois do início.
 * - C6: nome não vazio.
 * - C8: domínio minúsculo, no formato que o estabelecimento:criar aceita. Domínio nulo continua
 *   valendo (estabelecimento "fora do ar", decisão do BD-34), assim como user_id nulo.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement(<<<'SQL'
            ALTER TABLE estabelecimentos
                ADD CONSTRAINT chk_estabelecimentos_expediente CHECK (hora_fechamento > hora_abertura),
                ADD CONSTRAINT chk_estabelecimentos_intervalo CHECK (
                    (intervalo_inicio IS NULL AND intervalo_fim IS NULL)
                    OR (
                        intervalo_inicio IS NOT NULL AND intervalo_fim IS NOT NULL
                        AND intervalo_inicio >= hora_abertura
                        AND intervalo_fim > intervalo_inicio
                        AND intervalo_fim <= hora_fechamento
                    )
                ),
                ADD CONSTRAINT chk_estabelecimentos_nome CHECK (TRIM(nome) <> ''),
                ADD CONSTRAINT chk_estabelecimentos_dominio CHECK (
                    dominio IS NULL OR REGEXP_LIKE(dominio, '^[a-z0-9]([a-z0-9.-]*[a-z0-9])?$', 'c')
                )
            SQL);
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement(<<<'SQL'
            ALTER TABLE estabelecimentos
                DROP CHECK chk_estabelecimentos_expediente,
                DROP CHECK chk_estabelecimentos_intervalo,
                DROP CHECK chk_estabelecimentos_nome,
                DROP CHECK chk_estabelecimentos_dominio
            SQL);
    }
};
