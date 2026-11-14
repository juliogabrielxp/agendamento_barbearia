<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Providers\AppServiceProvider;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Em produção, comandos que apagam o banco inteiro (migrate:fresh, db:wipe...) precisam
 * se recusar a rodar, mesmo que alguém (ou um agente) digite o comando no servidor.
 */
class ComandosDestrutivosEmProducaoTest extends TestCase
{
    /**
     * Os comandos rodam contra um SQLite em memória à parte: um db:wipe de verdade na conexão
     * padrão apagaria as tabelas do MySQL de teste e derrubaria o resto da suíte.
     */
    private const DESCARTAVEL = 'descartavel';

    protected function setUp(): void
    {
        parent::setUp();

        config(['database.connections.'.self::DESCARTAVEL => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '']]);
    }

    protected function tearDown(): void
    {
        DB::prohibitDestructiveCommands(false);

        parent::tearDown();
    }

    public function test_migrate_fresh_e_db_wipe_se_recusam_em_producao(): void
    {
        $this->app['env'] = 'production';
        (new AppServiceProvider($this->app))->boot();

        $this->artisan('migrate:fresh', ['--force' => true, '--database' => self::DESCARTAVEL])->assertFailed();
        $this->artisan('db:wipe', ['--force' => true, '--database' => self::DESCARTAVEL])->assertFailed();
    }

    public function test_fora_de_producao_os_comandos_continuam_liberados(): void
    {
        $this->app['env'] = 'local';
        (new AppServiceProvider($this->app))->boot();

        $this->artisan('db:wipe', ['--force' => true, '--database' => self::DESCARTAVEL])->assertSuccessful();
    }
}
