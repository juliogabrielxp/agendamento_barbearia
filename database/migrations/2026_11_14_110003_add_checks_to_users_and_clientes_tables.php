<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * BD-13, M4: contas. Só no MySQL; ver M1.
 *
 * - C6: nome não vazio em users e clientes.
 * - C7: toda conta entra de algum jeito (senha ou Google).
 * - N2: todo cliente tem conta. A FK era ON DELETE SET NULL, o que deixaria um cliente
 *   órfão se a conta fosse apagada; vira RESTRICT (o app não apaga contas hoje).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement(<<<'SQL'
            ALTER TABLE users
                ADD CONSTRAINT chk_users_nome CHECK (TRIM(nome) <> ''),
                ADD CONSTRAINT chk_users_forma_de_entrar CHECK (password IS NOT NULL OR google_id IS NOT NULL)
            SQL);

        DB::statement("ALTER TABLE clientes ADD CONSTRAINT chk_clientes_nome CHECK (TRIM(nome) <> '')");

        // A FK SET NULL não pode existir numa coluna NOT NULL: tira, muda a coluna, recria como RESTRICT
        Schema::table('clientes', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });
        Schema::table('clientes', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable(false)->change();
            $table->foreign('user_id')->references('id')->on('users')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        Schema::table('clientes', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });
        Schema::table('clientes', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable()->change();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
        });

        DB::statement('ALTER TABLE clientes DROP CHECK chk_clientes_nome');
        DB::statement(<<<'SQL'
            ALTER TABLE users
                DROP CHECK chk_users_nome,
                DROP CHECK chk_users_forma_de_entrar
            SQL);
    }
};
