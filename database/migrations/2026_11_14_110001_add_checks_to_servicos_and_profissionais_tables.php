<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * BD-13, M2: serviço com duração de pelo menos 1 minuto (C2), preço não negativo (C3)
 * e nome não vazio em serviço e profissional (C6). Só no MySQL; ver M1.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement(<<<'SQL'
            ALTER TABLE servicos
                ADD CONSTRAINT chk_servicos_duracao CHECK (duracao_em_minutos >= 1),
                ADD CONSTRAINT chk_servicos_preco CHECK (preco >= 0),
                ADD CONSTRAINT chk_servicos_nome CHECK (TRIM(nome) <> '')
            SQL);

        DB::statement("ALTER TABLE profissionais ADD CONSTRAINT chk_profissionais_nome CHECK (TRIM(nome) <> '')");
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement('ALTER TABLE profissionais DROP CHECK chk_profissionais_nome');
        DB::statement(<<<'SQL'
            ALTER TABLE servicos
                DROP CHECK chk_servicos_duracao,
                DROP CHECK chk_servicos_preco,
                DROP CHECK chk_servicos_nome
            SQL);
    }
};
