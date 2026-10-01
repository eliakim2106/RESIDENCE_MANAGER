<?php

namespace App\Services\Payments\Gateways;

use App\Exceptions\WorkflowException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * FedaPay (API v1), agrégateur de test : un compte sandbox s'ouvre avec une simple adresse e-mail.
 *
 * Une transaction est créée, puis un jeton donne l'adresse du guichet. FedaPay renvoie le client sur
 * l'adresse de retour ; les webhooks (à déclarer dans le tableau de bord FedaPay) désignent la transaction
 * par son identifiant FedaPay, conservé comme référence. Clés : FEDAPAY_SECRET_KEY, FEDAPAY_ENVIRONMENT.
 */
class FedaPayGateway implements PaymentGateway
{
    public function name(): string
    {
        return 'fedapay';
    }

    public function label(): string
    {
        return 'FedaPay'.($this->live() ? '' : ' (test)');
    }

    public function enabled(): bool
    {
        return filled(config('services.fedapay.secret_key'));
    }

    public function requiredKeys(): array
    {
        return ['FEDAPAY_SECRET_KEY', 'FEDAPAY_ENVIRONMENT'];
    }

    public function payableAmount(int $amount): int
    {
        return $amount;
    }

    public function initialize(array $payment): array
    {
        [$lastname, $firstname] = $this->splitName($payment['customer']['name']);

        $created = $this->send('post', '/transactions', [
            'description' => mb_substr($payment['description'], 0, 150),
            'amount' => $payment['amount'],
            'currency' => ['iso' => 'XOF'],
            'callback_url' => $payment['return_url'],
            'merchant_reference' => $payment['transaction_id'],
            'customer' => [
                'firstname' => $firstname,
                'lastname' => $lastname,
                'email' => $payment['customer']['email'],
            ],
        ]);

        $id = $this->transaction($created)['id'] ?? null;

        if (! $id) {
            $this->fail('création de la transaction', $payment['transaction_id'], $created);
        }

        $token = $this->send('post', "/transactions/{$id}/token");

        if (empty($token['url'])) {
            $this->fail('ouverture du guichet', $payment['transaction_id'], $token);
        }

        return ['payment_url' => $token['url'], 'reference' => (string) $id];
    }

    public function check(string $transactionId, ?string $reference): array
    {
        if (! $reference) {
            return ['status' => self::PENDING, 'amount' => null, 'method' => null, 'operator_id' => null, 'payload' => []];
        }

        $response = $this->send('get', "/transactions/{$reference}");
        $transaction = $this->transaction($response);

        return [
            'status' => match ($transaction['status'] ?? null) {
                'approved', 'transferred' => self::ACCEPTED,
                'declined', 'canceled', 'cancelled', 'refunded', 'expired' => self::REFUSED,
                default => self::PENDING,
            },
            'amount' => isset($transaction['amount']) ? (int) $transaction['amount'] : null,
            'method' => $transaction['mode'] ?? null,
            'operator_id' => isset($transaction['reference']) ? (string) $transaction['reference'] : null,
            'payload' => $response,
        ];
    }

    /**
     * Webhook FedaPay : { "name": "transaction.approved", "entity": { "id": 123, … } }.
     */
    public function notified(Request $request): array
    {
        $id = $request->input('entity.id', $request->input('id'));

        return ['transaction' => null, 'reference' => $id ? (string) $id : null];
    }

    /*
    |--------------------------------------------------------------------------
    | API
    |--------------------------------------------------------------------------
    */

    /**
     * @param  array<string, mixed>  $body
     * @return array<string, mixed>
     */
    private function send(string $method, string $path, array $body = []): array
    {
        if (! $this->enabled()) {
            throw new WorkflowException('Le paiement en ligne n’est pas encore activé.');
        }

        try {
            /** @var Response $response */
            $response = $this->http()->{$method}($path, $body);
        } catch (ConnectionException $exception) {
            Log::error('FedaPay injoignable', ['path' => $path, 'error' => $exception->getMessage()]);

            throw new WorkflowException('Le service de paiement est momentanément injoignable. Réessayez dans un instant.');
        }

        return $response->json() ?? [];
    }

    private function http(): PendingRequest
    {
        return Http::baseUrl($this->live() ? 'https://api.fedapay.com/v1' : 'https://sandbox-api.fedapay.com/v1')
            ->withToken((string) config('services.fedapay.secret_key'))
            ->acceptJson()
            ->asJson()
            ->timeout(20)
            ->retry(2, 500, throw: false);
    }

    private function live(): bool
    {
        return config('services.fedapay.environment') === 'live';
    }

    /**
     * FedaPay enveloppe la transaction dans la clé « v1/transaction ».
     *
     * @param  array<string, mixed>  $response
     * @return array<string, mixed>
     */
    private function transaction(array $response): array
    {
        return $response['v1/transaction'] ?? $response['transaction'] ?? [];
    }

    /**
     * @param  array<string, mixed>  $response
     */
    private function fail(string $step, string $transactionId, array $response): never
    {
        Log::warning("FedaPay : échec de la {$step}", ['transaction' => $transactionId, 'response' => $response]);

        throw new WorkflowException('Le paiement en ligne n’a pas pu être ouvert ('.($response['message'] ?? 'erreur inconnue').'). Réessayez dans un instant.');
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
