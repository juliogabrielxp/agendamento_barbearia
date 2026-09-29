<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
   public function up(): void
    {
        Schema::table('agendamentos', function (Blueprint $table) {
            $table->string('status')->default('confirmado')->after('inicio');
        });
    }

    public function down(): void
    {
        Schema::table('agendamentos', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
