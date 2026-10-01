<?php

namespace App\Services\Payments;

use App\Exceptions\WorkflowException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Client de l'API de paiement CinetPay (v2).
 *
 * 1. initialize() ouvre un guichet de paiement et renvoie l'adresse où envoyer le client ;
 * 2. après le paiement, CinetPay appelle l'adresse de notification et renvoie le client sur l'adresse de retour ;
 * 3. check() interroge CinetPay : seul ce statut fait foi (une notification ne suffit jamais à valider un paiement).
 *
 * Sans CINETPAY_API_KEY ni CINETPAY_SITE_ID dans .env, le paiement en ligne est désactivé.
 */
class CinetPay
{
    public const ACCEPTED = 'ACCEPTED';

    public const REFUSED = 'REFUSED';

    /**
     * Le paiement en ligne est configuré.
     */
    public static function enabled(): bool
    {
        return filled(config('services.cinetpay.api_key')) && filled(config('services.cinetpay.site_id'));
    }

    /**
     * CinetPay n'accepte en XOF que des montants multiples de 5 : arrondi au multiple supérieur.
     */
    public static function payableAmount(int $amount): int
    {
        return (int) (ceil($amount / 5) * 5);
    }

    /**
     * Ouvre le guichet de paiement.
     *
     * @param  array{transaction_id: string, amount: int, description: string, notify_url: string, return_url: string, customer: array{name: string, email: string, phone?: ?string}, metadata?: string}  $payment
     * @return array{payment_url: string, payment_token: string}
     */
    public function initialize(array $payment): array
    {
        [$surname, $name] = $this->splitName($payment['customer']['name']);

        $response = $this->request('/payment', [
            'transaction_id' => $payment['transaction_id'],
            'amount' => self::payableAmount($payment['amount']),
            'currency' => 'XOF',
            'description' => mb_substr(preg_replace('/[^\pL\pN \-.,]/u', ' ', $payment['description']), 0, 150),
            'notify_url' => $payment['notify_url'],
            'return_url' => $payment['return_url'],
            'channels' => config('services.cinetpay.channels', 'ALL'),
            'lang' => 'fr',
            'metadata' => $payment['metadata'] ?? '',
            'customer_name' => $name,
            'customer_surname' => $surname,
            'customer_email' => $payment['customer']['email'],
            'customer_phone_number' => $payment['customer']['phone'] ?? '',
            // Requis par CinetPay pour le paiement par carte
            'customer_address' => 'Abidjan',
            'customer_city' => 'Abidjan',
            'customer_country' => 'CI',
            'customer_state' => 'CI',
            'customer_zip_code' => '00225',
        ]);

        if (($response['code'] ?? null) !== '201' || empty($response['data']['payment_url'])) {
            Log::warning('CinetPay : ouverture du paiement refusée', ['transaction' => $payment['transaction_id'], 'response' => $response]);

            throw new WorkflowException('Le paiement en ligne n’a pas pu être ouvert ('.($response['description'] ?? $response['message'] ?? 'erreur inconnue').'). Réessayez dans un instant.');
        }

        return [
            'payment_url' => $response['data']['payment_url'],
            'payment_token' => (string) ($response['data']['payment_token'] ?? ''),
        ];
    }

    /**
     * Statut d'une transaction chez CinetPay.
     *
     * @return array{status: string, amount: ?int, method: ?string, operator_id: ?string, paid_at: ?string, payload: array<string, mixed>}
     */
    public function check(string $transactionId): array
    {
        $response = $this->request('/payment/check', ['transaction_id' => $transactionId]);
        $data = $response['data'] ?? [];

        return [
            'status' => strtoupper((string) ($data['status'] ?? $response['message'] ?? 'UNKNOWN')),
            'amount' => isset($data['amount']) ? (int) $data['amount'] : null,
            'method' => $data['payment_method'] ?? null,
            'operator_id' => $data['operator_id'] ?? null,
            'paid_at' => $data['payment_date'] ?? null,
            'payload' => $response,
        ];
    }

    /**
     * @param  array<string, mixed>  $body
     * @return array<string, mixed>
     */
    private function request(string $path, array $body): array
    {
        if (! self::enabled()) {
            throw new WorkflowException('Le paiement en ligne n’est pas encore activé.');
        }

        try {
            return $this->http()
                ->post($path, $body + [
                    'apikey' => config('services.cinetpay.api_key'),
                    'site_id' => config('services.cinetpay.site_id'),
                ])
                ->json() ?? [];
        } catch (ConnectionException $exception) {
            Log::error('CinetPay injoignable', ['path' => $path, 'error' => $exception->getMessage()]);

            throw new WorkflowException('Le service de paiement est momentanément injoignable. Réessayez dans un instant.');
        }
    }

    private function http(): PendingRequest
    {
        return Http::baseUrl(rtrim((string) config('services.cinetpay.base_url'), '/'))
            ->acceptJson()
            ->asJson()
            ->timeout(20)
            ->retry(2, 500, throw: false);
    }

    /**
     * « Awa Koné » → ['Koné', 'Awa'] (nom, prénom).
     *
     * @return array{0: string, 1: string}
     */
    private function splitName(string $fullName): array
    {
        $parts = preg_split('/\s+/', trim($fullName)) ?: [];
        $first = array_shift($parts) ?? 'Client';

        return [$parts !== [] ? implode(' ', $parts) : $first, $first];
    }
}
