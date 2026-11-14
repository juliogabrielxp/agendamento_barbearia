<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * BD-13, M1: o banco recusa agendamento com status desconhecido (C1) e com duração ou
 * preço impossíveis (as colunas do BD-3).
 *
 * Só no MySQL: o SQLite dos testes não aceita ADD CONSTRAINT; a prova é o composer test:mysql.
 * Antes do deploy, scripts/schema/verificar-violacoes.sql tem que dar zero em produção.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement(<<<'SQL'
            ALTER TABLE agendamentos
                ADD CONSTRAINT chk_agendamentos_status CHECK (status IN ('confirmado', 'concluido', 'cancelado')),
                ADD CONSTRAINT chk_agendamentos_duracao CHECK (duracao_em_minutos >= 1),
                ADD CONSTRAINT chk_agendamentos_preco CHECK (preco >= 0)
            SQL);
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement(<<<'SQL'
            ALTER TABLE agendamentos
                DROP CHECK chk_agendamentos_status,
                DROP CHECK chk_agendamentos_duracao,
                DROP CHECK chk_agendamentos_preco
            SQL);
    }
};
