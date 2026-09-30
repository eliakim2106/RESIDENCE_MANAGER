<?php

use App\Services\SubscriptionManager;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| ABONNEMENTS DES PROPRIÉTAIRES
|--------------------------------------------------------------------------
| Chaque jour : fin des essais, factures de renouvellement, suspensions après l'échéance.
| En production, le planificateur est lancé par une tâche cron : * * * * * php artisan schedule:run
*/

Artisan::command('subscriptions:process', function (SubscriptionManager $subscriptions) {
    $result = $subscriptions->process();

    $this->info("{$result['invoiced']} facture(s) émise(s), {$result['suspended']} abonnement(s) suspendu(s).");
})->purpose('Émet les factures d’abonnement et suspend les abonnements impayés');

Schedule::command('subscriptions:process')->dailyAt('06:00');
