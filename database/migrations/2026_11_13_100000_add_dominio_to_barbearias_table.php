<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Host pelo qual o estabelecimento é acessado (ex.: trancas.juliogabrieldev.com).
 * É o que define o tenant de cada requisição pública e de cliente.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('barbearias', function (Blueprint $table) {
            $table->string('dominio')->nullable()->unique()->after('nome');
        });
    }

    public function down(): void
    {
        Schema::table('barbearias', function (Blueprint $table) {
            $table->dropUnique(['dominio']);
            $table->dropColumn('dominio');
        });
    }
};
