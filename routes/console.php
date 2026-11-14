<?php

use App\Infrastructure\Persistence\Eloquent\CadastroPendenteModel;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Sem --model, o model:prune só procura em app/Models; os models daqui ficam em Infrastructure.
// Precisa do cron "* * * * * php artisan schedule:run" no servidor.
Schedule::command('model:prune', ['--model' => [CadastroPendenteModel::class]])->dailyAt('03:30');
