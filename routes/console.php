<?php

use App\Services\Payments\CinetPay;
use App\Services\Payments\OnlinePayments;
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

/*
|--------------------------------------------------------------------------
| PAIEMENT EN LIGNE (CinetPay)
|--------------------------------------------------------------------------
| Toutes les 10 minutes : revérifie les paiements restés en attente (notification perdue, site injoignable).
*/

Artisan::command('payments:sync', function (OnlinePayments $payments) {
    if (! CinetPay::enabled()) {
        $this->warn('Paiement en ligne désactivé : renseignez CINETPAY_API_KEY et CINETPAY_SITE_ID dans .env.');

        return;
    }

    $result = $payments->syncPending();

    $this->info("{$result['accepted']} paiement(s) confirmé(s), {$result['refused']} refusé(s), {$result['abandoned']} abandonné(s).");
})->purpose('Revérifie auprès de CinetPay les paiements en ligne en attente');

Schedule::command('payments:sync')->everyTenMinutes()->when(fn () => CinetPay::enabled());
