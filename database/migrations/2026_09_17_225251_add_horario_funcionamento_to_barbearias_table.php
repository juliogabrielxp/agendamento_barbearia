<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('barbearias', function (Blueprint $table) {
            $table->time('hora_abertura')->default('08:00:00');
            $table->time('hora_fechamento')->default('19:00:00');
            $table->time('intervalo_inicio')->nullable();
            $table->time('intervalo_fim')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('barbearias', function (Blueprint $table) {
            $table->dropColumn(['hora_abertura', 'hora_fechamento', 'intervalo_inicio', 'intervalo_fim']);
        });
    }

    
};
