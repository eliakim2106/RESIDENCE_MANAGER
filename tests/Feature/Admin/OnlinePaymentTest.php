<?php

namespace Tests\Feature\Admin;

use App\Enums\BillingCycle;
use App\Enums\InvoiceStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentState;
use App\Enums\SubscriptionStatus;
use App\Enums\TransactionStatus;
use App\Models\Payment;
use App\Models\Property;
use App\Models\Reservation;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Notifications\ReservationUpdated;
use App\Services\Payments\OnlinePayments;
use App\Services\SubscriptionManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Paiement en ligne avec CinetPay (API simulée) : ouverture, notification, retour, idempotence, factures, droits.
 */
class OnlinePaymentTest extends TestCase
{
    use RefreshDatabase;

    private User $client;

    private User $owner;

    private Reservation $reservation;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
        config(['payments.gateway' => 'cinetpay', 'services.cinetpay.api_key' => 'test-key', 'services.cinetpay.site_id' => '123456']);

        $this->client = User::factory()->create();
        $this->owner = User::factory()->owner()->create();
        $property = Property::factory()->for($this->owner, 'owner')->create();

        $this->reservation = Reservation::factory()->confirmed()->for($property)->create([
            'user_id' => $this->client->id,
            'total_amount' => 75002,
            'amount_paid' => 0,
            'payment_state' => PaymentState::Unpaid,
        ]);
    }

    /**
     * Réponses simulées de CinetPay : ouverture du guichet, puis statut renvoyé par la vérification.
     */
    private function fakeCinetPay(string $status = 'ACCEPTED', ?int $amount = null, string $method = 'OM'): void
    {
        Http::fake([
            '*/payment/check' => fn (HttpRequest $request) => Http::response([
                'code' => $status === 'ACCEPTED' ? '00' : '600',
                'message' => $status === 'ACCEPTED' ? 'SUCCES' : $status,
                'data' => ['status' => $status, 'amount' => (string) ($amount ?? 75005), 'currency' => 'XOF', 'payment_method' => $method, 'operator_id' => 'MP2610.0001', 'payment_date' => now()->toDateTimeString()],
            ]),
            '*/v2/payment' => Http::response([
                'code' => '201',
                'message' => 'CREATED',
                'data' => ['payment_token' => 'tok_123', 'payment_url' => 'https://checkout.cinetpay.com/payment/tok_123'],
            ]),
        ]);
    }

    private function startPayment(): Payment
    {
        $this->actingAs($this->client)
            ->post(route('admin.reservations.pay-online', $this->reservation))
            ->assertRedirect('https://checkout.cinetpay.com/payment/tok_123');

        return Payment::sole();
    }

    public function test_client_is_sent_to_cinetpay_with_an_amount_rounded_to_a_multiple_of_five(): void
    {
        $this->fakeCinetPay();
        $this->actingAs($this->client)->get(route('admin.reservations.show', $this->reservation))->assertSee('Payer en ligne');

        $payment = $this->startPayment();

        $this->assertSame(TransactionStatus::Pending, $payment->statut);
        $this->assertSame(75005, $payment->amount);
        $this->assertStringStartsWith('RES-', $payment->transaction_id);

        Http::assertSent(fn (HttpRequest $request) => str_ends_with($request->url(), '/v2/payment')
            && $request['amount'] === 75005
            && $request['site_id'] === '123456'
            && str_contains($request['notify_url'], '/paiements/en-ligne/cinetpay/notification'));
    }

    public function test_accepted_notification_settles_the_reservation_once(): void
    {
        $this->fakeCinetPay();
        $payment = $this->startPayment();

        $this->post(route('paiements.notify', 'cinetpay'), ['cpm_trans_id' => $payment->transaction_id, 'cpm_site_id' => '123456'])->assertOk();
        $this->post(route('paiements.notify', 'cinetpay'), ['cpm_trans_id' => $payment->transaction_id])->assertOk();

        $payment->refresh();
        $this->assertSame(TransactionStatus::Accepted, $payment->statut);
        $this->assertSame(PaymentMethod::MobileMoney, $payment->method);
        $this->assertSame('MP2610.0001', $payment->operator_reference);

        // Compté une seule fois malgré deux notifications
        $reservation = $this->reservation->fresh();
        $this->assertSame(75005, $reservation->amount_paid);
        $this->assertSame(PaymentState::Paid, $reservation->payment_state);

        Notification::assertSentToTimes($this->client, ReservationUpdated::class, 1);
        Notification::assertSentTo($this->owner, ReservationUpdated::class, fn ($n) => $n->event === ReservationUpdated::PAID_FOR_OWNER);
    }

    public function test_return_from_cinetpay_keeps_the_session_and_shows_the_result(): void
    {
        $this->fakeCinetPay(method: 'VISAM');
        $payment = $this->startPayment();

        // Retour en POST depuis CinetPay : aucune session ouverte (sinon le client serait déconnecté)
        $this->post(route('paiements.return', $payment->transaction_id), ['transaction_id' => $payment->transaction_id])
            ->assertRedirect(route('paiements.result', $payment->transaction_id))
            ->assertCookieMissing(config('session.cookie'));

        $this->assertSame(PaymentMethod::Card, $payment->fresh()->method);

        $this->actingAs($this->client)->get(route('paiements.result', $payment->transaction_id))
            ->assertRedirect(route('admin.reservations.show', $this->reservation))
            ->assertSessionHas('success');

        $this->actingAs(User::factory()->create())->get(route('paiements.result', $payment->transaction_id))->assertForbidden();
    }

    public function test_refused_payment_changes_nothing_on_the_reservation(): void
    {
        $this->fakeCinetPay('REFUSED');
        $payment = $this->startPayment();

        $this->post(route('paiements.notify', 'cinetpay'), ['cpm_trans_id' => $payment->transaction_id])->assertOk();

        $this->assertSame(TransactionStatus::Refused, $payment->fresh()->statut);
        $this->assertSame(0, $this->reservation->fresh()->amount_paid);

        $this->actingAs($this->client)->get(route('paiements.result', $payment->transaction_id))->assertSessionHas('error');
    }

    public function test_an_amount_lower_than_expected_is_never_accepted(): void
    {
        $this->fakeCinetPay('ACCEPTED', 100);
        $payment = $this->startPayment();

        $this->post(route('paiements.notify', 'cinetpay'), ['cpm_trans_id' => $payment->transaction_id])->assertOk();

        $this->assertSame(TransactionStatus::Pending, $payment->fresh()->statut);
        $this->assertSame(0, $this->reservation->fresh()->amount_paid);
    }

    public function test_owner_pays_a_subscription_invoice_online(): void
    {
        $this->fakeCinetPay(amount: 15000);
        $plan = SubscriptionPlan::factory()->create(['trial_days' => 0, 'monthly_price' => 15000]);
        $subscription = app(SubscriptionManager::class)->subscribe($this->owner, $plan, BillingCycle::Monthly);
        $invoice = $subscription->invoices()->sole();

        $this->actingAs($this->owner)->get(route('admin.abonnement.show'))->assertSee('Payer en ligne');
        $this->actingAs($this->owner)->post(route('admin.abonnement.pay-online', $invoice))->assertRedirect('https://checkout.cinetpay.com/payment/tok_123');

        $invoice->refresh();
        $this->assertStringStartsWith('ABO-', $invoice->transaction_id);

        $this->post(route('paiements.notify', 'cinetpay'), ['cpm_trans_id' => $invoice->transaction_id])->assertOk();

        $this->assertSame(InvoiceStatus::Paid, $invoice->fresh()->statut);
        $this->assertNull($invoice->fresh()->recorded_by);
        $this->assertSame(SubscriptionStatus::Active, $subscription->fresh()->statut);

        $this->actingAs($this->owner)->get(route('paiements.result', $invoice->transaction_id))
            ->assertRedirect(route('admin.abonnement.show'))
            ->assertSessionHas('success');

        // La facture d'un autre propriétaire ne se paie pas
        $this->actingAs(User::factory()->owner()->create())->post(route('admin.abonnement.pay-online', $invoice))->assertForbidden();
    }

    public function test_access_rules_and_disabled_mode(): void
    {
        $this->fakeCinetPay();

        // Seul le client de la réservation paie ; le propriétaire encaisse autrement
        $this->actingAs($this->owner)->post(route('admin.reservations.pay-online', $this->reservation))->assertForbidden();
        $this->actingAs(User::factory()->create())->post(route('admin.reservations.pay-online', $this->reservation))->assertForbidden();

        // Une réservation réglée ne se paie plus
        $this->reservation->update(['amount_paid' => 75002, 'payment_state' => PaymentState::Paid]);
        $this->actingAs($this->client)->post(route('admin.reservations.pay-online', $this->reservation))->assertSessionHas('error');

        // Sans clés : bouton masqué, paiement refusé, aucune requête vers CinetPay
        config(['services.cinetpay.api_key' => null]);
        $this->reservation->update(['amount_paid' => 0, 'payment_state' => PaymentState::Unpaid]);
        $this->actingAs($this->client)->get(route('admin.reservations.show', $this->reservation))->assertDontSee('Payer en ligne');
        $this->actingAs($this->client)->post(route('admin.reservations.pay-online', $this->reservation))->assertSessionHas('error');
        $this->actingAs(User::factory()->admin()->create())->get(route('admin.paiements.index'))->assertSee('Paiement en ligne non activé');
        Http::assertNothingSent();
    }

    public function test_scheduled_sync_confirms_late_payments_and_abandons_old_ones(): void
    {
        $this->fakeCinetPay();
        $payment = $this->startPayment();
        $payment->forceFill(['created_at' => now()->subMinutes(5)])->save();

        $this->assertSame(['accepted' => 1, 'refused' => 0, 'abandoned' => 0], app(OnlinePayments::class)->syncPending());
        $this->assertSame(TransactionStatus::Accepted, $payment->fresh()->statut);

        // Transaction jamais finalisée depuis plus de 48 h
        $this->fakeCinetPay('WAITING_FOR_CUSTOMER');
        $old = Payment::factory()->for($this->reservation)->create(['provider' => 'cinetpay', 'created_at' => now()->subDays(3)]);

        $this->assertSame(1, app(OnlinePayments::class)->syncPending()['abandoned']);
        $this->assertSame(TransactionStatus::Cancelled, $old->fresh()->statut);
    }
}
