<?php

declare(strict_types=1);

namespace Tests\Feature\Concerns;

use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Simula o servidor caindo logo depois de um comando SQL: a primeira query executada que
 * contém o trecho lança exceção, como se o processo morresse antes do passo seguinte.
 * O trecho é comparado sem aspas nem crases, então vale igual no SQLite e no MySQL.
 */
trait SimulaQuedaNoMeio
{
    private function cairDepoisDe(string $trechoSql): void
    {
        $caiu = false;

        DB::listen(function (QueryExecuted $query) use ($trechoSql, &$caiu): void {
            if ($caiu || ! str_contains(str_replace(['`', '"'], '', $query->sql), $trechoSql)) {
                return;
            }

            $caiu = true;

            throw new RuntimeException("Queda simulada depois de: {$trechoSql}");
        });
    }
}
