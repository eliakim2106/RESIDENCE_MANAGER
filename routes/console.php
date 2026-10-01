<?php

use App\Services\Payments\OnlinePayments;
use App\Services\Payments\PaymentGateways;
use App\Services\ReservationWorkflow;
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
| Chaque jour : fin des essais, factures de renouvellement, rappels avant l'échéance, suspensions après l'échéance.
| En production, le planificateur est lancé par une tâche cron : * * * * * php artisan schedule:run
*/

Artisan::command('subscriptions:process', function (SubscriptionManager $subscriptions) {
    $result = $subscriptions->process();

    $this->info("{$result['invoiced']} facture(s) émise(s), {$result['reminded']} rappel(s) d’échéance, {$result['suspended']} abonnement(s) suspendu(s).");
})->purpose('Émet les factures d’abonnement et suspend les abonnements impayés');

Schedule::command('subscriptions:process')->dailyAt('06:00');

/*
|--------------------------------------------------------------------------
| PAIEMENT EN LIGNE (CinetPay, FedaPay)
|--------------------------------------------------------------------------
| Toutes les 10 minutes : revérifie les paiements restés en attente (notification perdue, site injoignable).
*/

Artisan::command('payments:sync', function (OnlinePayments $payments) {
    if (! PaymentGateways::available()) {
        $this->warn('Paiement en ligne désactivé : renseignez '.implode(', ', PaymentGateways::configured()->requiredKeys()).' dans .env.');

        return;
    }

    $result = $payments->syncPending();

    $this->info("{$result['accepted']} paiement(s) confirmé(s), {$result['refused']} refusé(s), {$result['abandoned']} abandonné(s).");
})->purpose('Revérifie auprès de l’agrégateur les paiements en ligne en attente');

Schedule::command('payments:sync')->everyTenMinutes()->when(fn () => PaymentGateways::available());

/*
|--------------------------------------------------------------------------
| RÉSERVATIONS
|--------------------------------------------------------------------------
| Chaque matin : chaque propriétaire reçoit la liste des clients qui arrivent le lendemain.
*/

Artisan::command('reservations:remind-arrivals', function (ReservationWorkflow $reservations) {
    $owners = $reservations->remindArrivals();

    $this->info("{$owners} propriétaire(s) prévenu(s) des arrivées de demain.");
})->purpose('Prévient chaque propriétaire des arrivées du lendemain');

Schedule::command('reservations:remind-arrivals')->dailyAt('07:00');
