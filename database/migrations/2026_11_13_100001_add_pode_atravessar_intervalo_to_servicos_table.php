<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Serviços longos (ex.: tranças) podem ocupar o horário do intervalo,
 * desde que não comecem dentro dele.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('servicos', function (Blueprint $table) {
            $table->boolean('pode_atravessar_intervalo')->default(false)->after('preco');
        });
    }

    public function down(): void
    {
        Schema::table('servicos', function (Blueprint $table) {
            $table->dropColumn('pode_atravessar_intervalo');
        });
    }
};
