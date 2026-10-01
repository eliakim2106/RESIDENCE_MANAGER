<?php

namespace App\Services\Payments\Gateways;

use App\Exceptions\WorkflowException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * CinetPay (API v2), agrégateur de production.
 *
 * L'adresse de notification est transmise avec chaque transaction ; la vérification se fait
 * avec notre identifiant de transaction. Clés : CINETPAY_API_KEY, CINETPAY_SITE_ID, CINETPAY_SECRET_KEY.
 */
class CinetPayGateway implements PaymentGateway
{
    public function name(): string
    {
        return 'cinetpay';
    }

    public function label(): string
    {
        return 'CinetPay';
    }

    public function enabled(): bool
    {
        return filled(config('services.cinetpay.api_key')) && filled(config('services.cinetpay.site_id'));
    }

    public function requiredKeys(): array
    {
        return ['CINETPAY_API_KEY', 'CINETPAY_SITE_ID', 'CINETPAY_SECRET_KEY'];
    }

    /**
     * CinetPay n'accepte en XOF que des montants multiples de 5 : arrondi au multiple supérieur.
     */
    public function payableAmount(int $amount): int
    {
        return (int) (ceil($amount / 5) * 5);
    }

    public function initialize(array $payment): array
    {
        [$surname, $name] = $this->splitName($payment['customer']['name']);

        $response = $this->request('/payment', [
            'transaction_id' => $payment['transaction_id'],
            'amount' => $this->payableAmount($payment['amount']),
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
            'reference' => (string) ($response['data']['payment_token'] ?? '') ?: null,
        ];
    }

    public function check(string $transactionId, ?string $reference): array
    {
        $response = $this->request('/payment/check', ['transaction_id' => $transactionId]);
        $data = $response['data'] ?? [];
        $status = strtoupper((string) ($data['status'] ?? $response['message'] ?? 'UNKNOWN'));

        return [
            'status' => match (true) {
                $status === 'ACCEPTED' => self::ACCEPTED,
                in_array($status, ['REFUSED', 'CANCELED', 'CANCELLED', 'FAILED'], true) => self::REFUSED,
                default => self::PENDING,
            },
            'amount' => isset($data['amount']) ? (int) $data['amount'] : null,
            'method' => $data['payment_method'] ?? null,
            'operator_id' => $data['operator_id'] ?? null,
            'payload' => $response,
        ];
    }

    /**
     * CinetPay envoie notre identifiant de transaction (cpm_trans_id).
     */
    public function notified(Request $request): array
    {
        return [
            'transaction' => $request->input('cpm_trans_id', $request->input('transaction_id')) ?: null,
            'reference' => null,
        ];
    }

    /**
     * @param  array<string, mixed>  $body
     * @return array<string, mixed>
     */
    private function request(string $path, array $body): array
    {
        if (! $this->enabled()) {
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
