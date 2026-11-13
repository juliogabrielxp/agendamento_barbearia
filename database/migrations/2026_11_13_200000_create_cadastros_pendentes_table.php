<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cadastros_pendentes', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();
            $table->string('nome');
            $table->string('senha_hash');
            $table->string('codigo_hash');
            $table->string('chave_hash', 64);
            $table->unsignedTinyInteger('tentativas')->default(0);
            $table->dateTime('expira_em');
            $table->dateTime('enviado_em');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cadastros_pendentes');
    }
};
