<?php

namespace App\Services\Payments;

use App\Services\Payments\Gateways\PaymentGateway;
use InvalidArgumentException;

/**
 * Choix de l'agrégateur : PAYMENT_GATEWAY dans .env (cinetpay en production, fedapay pour les tests).
 * Un paiement déjà ouvert est toujours revérifié auprès de l'agrégateur qui l'a ouvert.
 */
class PaymentGateways
{
    /**
     * Agrégateur configuré dans .env, qu'il ait ou non ses clés.
     */
    public static function configured(): PaymentGateway
    {
        return self::driver((string) config('payments.gateway'));
    }

    /**
     * Agrégateur utilisable pour un nouveau paiement, ou null si ses clés manquent.
     */
    public static function current(): ?PaymentGateway
    {
        $gateway = self::configured();

        return $gateway->enabled() ? $gateway : null;
    }

    /**
     * Le paiement en ligne est proposé aux utilisateurs.
     */
    public static function available(): bool
    {
        return self::current() !== null;
    }

    public static function driver(string $name): PaymentGateway
    {
        $class = config("payments.gateways.{$name}");

        if (! $class) {
            throw new InvalidArgumentException("Agrégateur de paiement inconnu : {$name}.");
        }

        return app($class);
    }

    /**
     * Noms de tous les agrégateurs (valeurs possibles de la colonne provider des paiements en ligne).
     *
     * @return list<string>
     */
    public static function names(): array
    {
        return array_keys(config('payments.gateways'));
    }
}
