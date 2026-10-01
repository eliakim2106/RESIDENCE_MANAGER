<?php

use App\Services\Payments\Gateways\CinetPayGateway;
use App\Services\Payments\Gateways\FedaPayGateway;

return [

    /*
    |--------------------------------------------------------------------------
    | Agrégateur de paiement en ligne
    |--------------------------------------------------------------------------
    | cinetpay : production (compte entreprise, RCCM requis).
    | fedapay  : tests en local (compte sandbox ouvert avec une simple adresse e-mail).
    | Les clés de chaque agrégateur sont dans config/services.php.
    */

    'gateway' => env('PAYMENT_GATEWAY', 'cinetpay'),

    'gateways' => [
        'cinetpay' => CinetPayGateway::class,
        'fedapay' => FedaPayGateway::class,
    ],

];
