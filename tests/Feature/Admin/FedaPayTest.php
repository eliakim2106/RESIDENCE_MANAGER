<?php

namespace Tests\Feature\Admin;

use App\Enums\BillingCycle;
use App\Enums\InvoiceStatus;
use App\Enums\PaymentState;
use App\Enums\TransactionStatus;
use App\Models\Payment;
use App\Models\Property;
use App\Models\Reservation;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\SubscriptionManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Paiement en ligne avec FedaPay (agrégateur de test, API simulée) et bascule entre agrégateurs.
 */
class FedaPayTest extends TestCase
{
    use RefreshDatabase;

    private User $client;

    private Reservation $reservation;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
        config([
            'payments.gateway' => 'fedapay',
            'services.fedapay.secret_key' => 'sk_sandbox_test',
            'services.fedapay.environment' => 'sandbox',
        ]);

        $this->client = User::factory()->create();
        $property = Property::factory()->for(User::factory()->owner(), 'owner')->create();

        $this->reservation = Reservation::factory()->confirmed()->for($property)->create([
            'user_id' => $this->client->id,
            'total_amount' => 75002,
            'amount_paid' => 0,
            'payment_state' => PaymentState::Unpaid,
        ]);
    }

    /**
     * Réponses simulées de FedaPay : création de la transaction (id 4321), jeton du guichet, puis statut.
     */
    private function fakeFedaPay(string $status = 'approved', int $amount = 75002): void
    {
        Http::fake([
            'sandbox-api.fedapay.com/v1/transactions/4321/token' => Http::response(['token' => 'tok_abc', 'url' => 'https://sandbox-process.fedapay.com/tok_abc']),
            'sandbox-api.fedapay.com/v1/transactions/4321' => Http::response(['v1/transaction' => ['id' => 4321, 'status' => $status, 'amount' => $amount, 'mode' => 'mtn_open', 'reference' => 'trx_Gb8_1700000000']]),
            'sandbox-api.fedapay.com/v1/transactions' => Http::response(['v1/transaction' => ['id' => 4321, 'status' => 'pending', 'amount' => $amount]]),
        ]);
    }

    public function test_client_pays_with_fedapay_and_the_webhook_settles_the_reservation(): void
    {
        $this->fakeFedaPay();

        $this->actingAs($this->client)
            ->post(route('admin.reservations.pay-online', $this->reservation))
            ->assertRedirect('https://sandbox-process.fedapay.com/tok_abc');

        $payment = Payment::sole();
        $this->assertSame('fedapay', $payment->provider);
        $this->assertSame('4321', $payment->payment_token);
        $this->assertSame(75002, $payment->amount, 'Pas d’arrondi au multiple de 5 avec FedaPay');

        Http::assertSent(fn (HttpRequest $request) => $request->url() === 'https://sandbox-api.fedapay.com/v1/transactions'
            && $request->hasHeader('Authorization', 'Bearer sk_sandbox_test')
            && $request['currency'] === ['iso' => 'XOF']
            && str_contains($request['callback_url'], '/paiements/en-ligne/retour/'.$payment->transaction_id));

        // Webhook FedaPay : la transaction est désignée par l'identifiant FedaPay
        $this->postJson(route('paiements.notify', 'fedapay'), ['name' => 'transaction.approved', 'entity' => ['id' => 4321]])->assertOk();

        $this->assertSame(TransactionStatus::Accepted, $payment->fresh()->statut);
        $this->assertSame('trx_Gb8_1700000000', $payment->fresh()->operator_reference);
        $this->assertSame(PaymentState::Paid, $this->reservation->fresh()->payment_state);
    }

    public function test_return_from_fedapay_shows_the_result(): void
    {
        $this->fakeFedaPay('declined');
        $this->actingAs($this->client)->post(route('admin.reservations.pay-online', $this->reservation));
        $payment = Payment::sole();

        // FedaPay renvoie le client en GET avec ?id=…&status=…
        $this->get(route('paiements.return', $payment->transaction_id).'?id=4321&status=declined')
            ->assertRedirect(route('paiements.result', $payment->transaction_id));

        $this->assertSame(TransactionStatus::Refused, $payment->fresh()->statut);
        $this->actingAs($this->client)->get(route('paiements.result', $payment->transaction_id))->assertSessionHas('error');
    }

    public function test_owner_pays_an_invoice_with_fedapay(): void
    {
        $this->fakeFedaPay(amount: 15000);
        $owner = User::factory()->owner()->create();
        $plan = SubscriptionPlan::factory()->create(['trial_days' => 0, 'monthly_price' => 15000]);
        $invoice = app(SubscriptionManager::class)->subscribe($owner, $plan, BillingCycle::Monthly)->invoices()->sole();

        $this->actingAs($owner)->post(route('admin.abonnement.pay-online', $invoice))->assertRedirect('https://sandbox-process.fedapay.com/tok_abc');

        $invoice->refresh();
        $this->assertSame('fedapay', $invoice->gateway);
        $this->assertSame('4321', $invoice->gateway_reference);

        $this->postJson(route('paiements.notify', 'fedapay'), ['name' => 'transaction.approved', 'entity' => ['id' => 4321]])->assertOk();

        $this->assertSame(InvoiceStatus::Paid, $invoice->fresh()->statut);
    }

    public function test_a_payment_is_checked_with_the_gateway_that_opened_it(): void
    {
        $this->fakeFedaPay();
        $this->actingAs($this->client)->post(route('admin.reservations.pay-online', $this->reservation));
        $payment = Payment::sole();

        // Passage en production sur CinetPay : le paiement FedaPay en cours est toujours vérifié chez FedaPay
        config(['payments.gateway' => 'cinetpay', 'services.cinetpay.api_key' => 'k', 'services.cinetpay.site_id' => '1']);

        $this->get(route('paiements.return', $payment->transaction_id))->assertRedirect();

        $this->assertSame(TransactionStatus::Accepted, $payment->fresh()->statut);
        Http::assertNotSent(fn (HttpRequest $request) => str_contains($request->url(), 'cinetpay'));
    }

    public function test_unknown_gateway_and_missing_key(): void
    {
        $this->assertContains($this->post('/paiements/en-ligne/inconnu/notification')->status(), [404, 405]);

        config(['services.fedapay.secret_key' => null]);
        $this->actingAs($this->client)->get(route('admin.reservations.show', $this->reservation))->assertDontSee('Payer en ligne');
        $this->actingAs(User::factory()->admin()->create())->get(route('admin.paiements.index'))
            ->assertSee('Paiement en ligne non activé (FedaPay (test))')
            ->assertSee('FEDAPAY_SECRET_KEY');
    }
}
