<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Um usuário tem no máximo um perfil de barbearia e um de cliente.
 * O código sempre busca o perfil com where('user_id')->first(), então um duplicado
 * faria o perfil "certo" depender da ordem do banco.
 */
return new class extends Migration
{
    private const TABELAS = ['barbearias', 'clientes'];

    public function up(): void
    {
        // Não resolve duplicados sozinha: os agendamentos apontam para o cliente_id,
        // então decidir qual perfil fica é uma decisão manual.
        foreach (self::TABELAS as $tabela) {
            $duplicados = DB::table($tabela)
                ->select('user_id')
                ->whereNotNull('user_id')
                ->groupBy('user_id')
                ->havingRaw('COUNT(*) > 1')
                ->pluck('user_id');

            if ($duplicados->isNotEmpty()) {
                throw new RuntimeException(sprintf(
                    'A tabela %s tem mais de um perfil para os user_id [%s]. Una os registros antes de rodar esta migration.',
                    $tabela,
                    $duplicados->implode(', ')
                ));
            }
        }

        foreach (self::TABELAS as $tabela) {
            Schema::table($tabela, function (Blueprint $table) {
                $table->unique('user_id');
            });
        }
    }

    public function down(): void
    {
        foreach (self::TABELAS as $tabela) {
            Schema::table($tabela, function (Blueprint $table) use ($tabela) {
                // No MySQL o índice único passou a servir a FK de user_id (o índice
                // automático dela foi descartado); sem recriar um índice simples antes,
                // o MySQL recusa o drop com o erro 1553.
                $table->index('user_id', "{$tabela}_user_id_foreign");
                $table->dropUnique(['user_id']);
            });
        }
    }
};
