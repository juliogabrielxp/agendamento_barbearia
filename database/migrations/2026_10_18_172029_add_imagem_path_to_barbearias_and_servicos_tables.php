<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('barbearias', function (Blueprint $table) {
            $table->string('imagem_path')->nullable();
        });

        Schema::table('servicos', function (Blueprint $table) {
            $table->string('imagem_path')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('barbearias', function (Blueprint $table) {
            $table->dropColumn('imagem_path');
        });

        Schema::table('servicos', function (Blueprint $table) {
            $table->dropColumn('imagem_path');
        });
    }
};
