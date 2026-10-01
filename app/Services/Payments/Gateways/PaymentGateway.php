<?php

namespace App\Services\Payments\Gateways;

use Illuminate\Http\Request;

/**
 * Agrégateur de paiement en ligne (CinetPay en production, FedaPay en test…).
 *
 * Statuts normalisés renvoyés par check() : « accepted », « refused » ou « pending ».
 */
interface PaymentGateway
{
    public const ACCEPTED = 'accepted';

    public const REFUSED = 'refused';

    public const PENDING = 'pending';

    /**
     * Identifiant enregistré sur les paiements (colonne provider), ex. « cinetpay ».
     */
    public function name(): string;

    /**
     * Nom affiché, ex. « CinetPay ».
     */
    public function label(): string;

    /**
     * Les clés de l'agrégateur sont renseignées dans .env.
     */
    public function enabled(): bool;

    /**
     * Variables .env attendues (affichées à l'administrateur tant qu'elles manquent).
     *
     * @return list<string>
     */
    public function requiredKeys(): array;

    /**
     * Montant réellement demandé au client (certains agrégateurs imposent un arrondi).
     */
    public function payableAmount(int $amount): int;

    /**
     * Ouvre le guichet de paiement.
     *
     * @param  array{transaction_id: string, amount: int, description: string, notify_url: string, return_url: string, customer: array{name: string, email: string, phone?: ?string}, metadata?: string}  $payment
     * @return array{payment_url: string, reference: ?string} reference : identifiant de la transaction chez l'agrégateur
     */
    public function initialize(array $payment): array;

    /**
     * Statut de la transaction chez l'agrégateur : seul ce statut fait foi.
     *
     * @return array{status: string, amount: ?int, method: ?string, operator_id: ?string, payload: array<string, mixed>}
     */
    public function check(string $transactionId, ?string $reference): array;

    /**
     * Transaction visée par une notification reçue de l'agrégateur.
     *
     * @return array{transaction: ?string, reference: ?string}
     */
    public function notified(Request $request): array;
}
