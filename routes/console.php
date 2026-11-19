<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Cycle de vie des abonnements : échéances, période de grâce, expirations et
// rappels de renouvellement. Une exécution horaire évite tout retard visible.
Schedule::command('subscriptions:process')
    ->hourly()
    ->withoutOverlapping();
