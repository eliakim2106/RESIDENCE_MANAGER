<?php

namespace Tests\Feature\Admin;

use App\Enums\BillingCycle;
use App\Enums\PayoutMethod;
use App\Enums\PayoutStatus;
use App\Models\Payment;
use App\Models\Payout;
use App\Models\Property;
use App\Models\Reservation;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Notifications\PayoutRecorded;
use App\Services\PayoutLedger;
use App\Services\ReservationWorkflow;
use App\Services\SubscriptionManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Reversements aux propriétaires : solde, commission, enregistrement, remboursement ultérieur, annulation et droits.
 */
class PayoutTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Property $property;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();

        // Propriétaire sur une formule à 10 % de commission
        $this->owner = User::factory()->owner()->create();
        $plan = SubscriptionPlan::factory()->create(['commission_rate' => 10]);
        app(SubscriptionManager::class)->subscribe($this->owner, $plan, BillingCycle::Monthly);
        $this->property = Property::factory()->for($this->owner, 'owner')->create();
    }

    /**
     * Paiement en ligne accepté sur une réservation passée (completed) ou à venir (confirmed).
     */
    private function payment(int $amount, string $state = 'completed', string $provider = 'cinetpay'): Payment
    {
        $reservation = Reservation::factory()->{$state}()->for($this->property)->create(['total_amount' => $amount]);

        return Payment::factory()->accepted()->for($reservation)->create(['amount' => $amount, 'provider' => $provider]);
    }

    private function ledger(): PayoutLedger
    {
        return app(PayoutLedger::class);
    }

    public function test_balance_is_online_payments_minus_commission(): void
    {
        $this->payment(100000);
        $this->payment(40000, 'confirmed');
        $this->payment(50000, 'completed', 'manuel');

        $balance = $this->ledger()->balance($this->owner);

        $this->assertSame(90000, $balance['available']);
        $this->assertSame(10000, $balance['commission']);
        $this->assertSame(36000, $balance['upcoming']);
        $this->assertCount(2, $balance['lines']);
    }

    public function test_admin_records_a_payout_and_the_owner_is_notified(): void
    {
        $this->payment(100000);
        $this->payment(40000, 'confirmed');
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get(route('admin.reversements.index'))->assertOk()->assertSee($this->owner->name)->assertSee('90 000 FCFA');
        $this->actingAs($admin)->get(route('admin.reversements.owner', $this->owner))->assertOk()->assertSee('Reverser 90 000 FCFA');

        $this->actingAs($admin)->post(route('admin.reversements.store', $this->owner), ['moyen' => 'mobile_money', 'reference' => 'OM-778'])
            ->assertSessionHas('success');

        $payout = Payout::sole();
        $this->assertSame(90000, $payout->amount);
        $this->assertSame(100000, $payout->gross_amount);
        $this->assertSame(10000, $payout->commission_amount);
        $this->assertSame(1, $payout->items()->count());
        $this->assertStringStartsWith('REV-', $payout->number);

        // Seul le montant à venir reste
        $balance = $this->ledger()->balance($this->owner);
        $this->assertSame(0, $balance['available']);
        $this->assertSame(36000, $balance['upcoming']);

        Notification::assertSentTo($this->owner, PayoutRecorded::class);

        // Le propriétaire voit son reversement et son relevé ; un autre propriétaire non
        $this->actingAs($this->owner)->get(route('admin.mes-reversements.index'))->assertOk()->assertSee($payout->number);
        $this->actingAs($this->owner)->get(route('admin.reversements.statement', $payout))->assertOk()->assertSee('90 000 FCFA');
        $this->actingAs(User::factory()->owner()->create())->get(route('admin.reversements.statement', $payout))->assertForbidden();
    }

    public function test_refund_after_a_payout_is_deducted_from_the_next_one(): void
    {
        $payment = $this->payment(100000);
        $admin = User::factory()->admin()->create();
        $this->ledger()->record($this->owner, PayoutMethod::MobileMoney, null, null, $admin);

        app(ReservationWorkflow::class)->refundPayment($payment->fresh(), 20000, 'Geste commercial');

        $this->assertSame(-18000, $this->ledger()->balance($this->owner)['available']);

        // Rien à reverser tant que le solde est négatif
        $this->actingAs($admin)->post(route('admin.reversements.store', $this->owner), ['moyen' => 'cash'])->assertSessionHas('error');

        $this->payment(50000);
        $second = $this->ledger()->record($this->owner, PayoutMethod::BankTransfer, 'VIR-1', null, $admin);

        $this->assertSame(27000, $second->amount);
        $this->assertSame(2, $second->items()->count());
        $this->assertSame(0, $this->ledger()->balance($this->owner)['available']);
    }

    public function test_cancelling_a_payout_makes_the_amount_payable_again(): void
    {
        $this->payment(100000);
        $admin = User::factory()->admin()->create();
        $payout = $this->ledger()->record($this->owner, PayoutMethod::MobileMoney, null, null, $admin);

        $this->actingAs($admin)->patch(route('admin.reversements.cancel', $payout))->assertSessionHas('success');

        $this->assertSame(PayoutStatus::Cancelled, $payout->fresh()->statut);
        $this->assertSame(90000, $this->ledger()->balance($this->owner)['available']);
        $this->actingAs($admin)->patch(route('admin.reversements.cancel', $payout))->assertSessionHas('error');
    }

    public function test_owner_saves_payout_details(): void
    {
        $this->actingAs($this->owner)->put(route('admin.mes-reversements.account'), [
            'moyen' => 'mobile_money',
            'compte' => '07 00 00 00 00',
            'titulaire' => 'Awa Koné',
        ])->assertSessionHas('success');

        $this->assertSame('Mobile Money · 07 00 00 00 00 (Awa Koné)', $this->owner->fresh()->payoutAccountSummary());

        // Les coordonnées sont reprises sur le reversement
        $this->payment(10000);
        $payout = $this->ledger()->record($this->owner->fresh(), PayoutMethod::MobileMoney, null, null, User::factory()->admin()->create());
        $this->assertSame('Mobile Money · 07 00 00 00 00 (Awa Koné)', $payout->account);
    }

    public function test_access_rules(): void
    {
        $this->actingAs($this->owner)->get(route('admin.reversements.index'))->assertForbidden();
        $this->actingAs($this->owner)->get(route('admin.reversements.owner', $this->owner))->assertForbidden();
        $this->actingAs(User::factory()->create())->get(route('admin.mes-reversements.index'))->assertForbidden();

        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)->get(route('admin.reversements.owner', $admin))->assertNotFound();
        $this->actingAs($admin)->get(route('admin.reversements.index', ['statut' => 'historique']))->assertOk();
    }
}
