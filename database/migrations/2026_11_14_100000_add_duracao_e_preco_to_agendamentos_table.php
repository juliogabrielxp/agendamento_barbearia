<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * O agendamento passa a guardar a duração e o preço do serviço no momento da reserva (BD-3).
 * Antes o conflito usava a duração atual do serviço: encurtar um serviço liberava, na agenda,
 * o tempo de quem já tinha reservado. Os agendamentos existentes recebem os valores atuais
 * do serviço, que é o melhor que se sabe sobre eles.
 *
 * Rode com o usuário de migração (composer migrate / --database=mysql_migracao) e só
 * depois de ter backup (BD-1): a tabela é reconstruída ao virar NOT NULL.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agendamentos', function (Blueprint $table) {
            $table->unsignedInteger('duracao_em_minutos')->nullable()->after('inicio');
            $table->decimal('preco', 8, 2)->nullable()->after('duracao_em_minutos');
        });

        DB::statement(<<<'SQL'
            UPDATE agendamentos SET
                duracao_em_minutos = (SELECT s.duracao_em_minutos FROM servicos s WHERE s.id = agendamentos.servico_id),
                preco = (SELECT s.preco FROM servicos s WHERE s.id = agendamentos.servico_id)
            SQL);

        Schema::table('agendamentos', function (Blueprint $table) {
            $table->unsignedInteger('duracao_em_minutos')->nullable(false)->change();
            $table->decimal('preco', 8, 2)->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('agendamentos', function (Blueprint $table) {
            $table->dropColumn(['duracao_em_minutos', 'preco']);
        });
    }
};
