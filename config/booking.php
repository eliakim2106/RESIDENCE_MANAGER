<?php

/*
|--------------------------------------------------------------------------
| Réservation en ligne (site public)
|--------------------------------------------------------------------------
*/

return [

    // Délai laissé à l'établissement pour valider une demande ; passé ce délai, les unités sont libérées
    'request_ttl_hours' => (int) env('BOOKING_REQUEST_TTL_HOURS', 48),

    // Frais de service ajoutés au séjour, en % (0 : aucun frais). À fixer par DS Holding.
    'service_fee_rate' => (float) env('BOOKING_SERVICE_FEE_RATE', 0),

    // Séjour le plus long réservable en ligne (nuits)
    'max_nights' => (int) env('BOOKING_MAX_NIGHTS', 60),

    // Réservation possible jusqu'à combien de jours à l'avance
    'max_days_ahead' => (int) env('BOOKING_MAX_DAYS_AHEAD', 365),

];
