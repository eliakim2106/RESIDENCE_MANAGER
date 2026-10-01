<?php

namespace Tests\Feature\Admin;

use App\Enums\PaymentMethod;
use App\Enums\PaymentState;
use App\Enums\TransactionStatus;
use App\Models\Payment;
use App\Models\Property;
use App\Models\Reservation;
use App\Models\User;
use App\Notifications\ReservationUpdated;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\Concerns\ReadsExcelExports;
use Tests\TestCase;

/**
 * Module Paiements : synthèse, filtres, fiche, reçu, export et remboursement partiel.
 */
class PaymentModuleTest extends TestCase
{
    use ReadsExcelExports, RefreshDatabase;

    private function payment(array $attributes = [], ?Reservation $reservation = null): Payment
    {
        return Payment::factory()->accepted()->for($reservation ?? Reservation::factory()->confirmed()->create())->create($attributes);
    }

    public function test_summary_and_filters_by_period_and_method(): void
    {
        $admin = User::factory()->admin()->create();
        $today = $this->payment(['amount' => 50000, 'method' => PaymentMethod::Cash, 'paid_at' => now(), 'operator_reference' => 'RECU-TODAY']);
        $old = $this->payment(['amount' => 30000, 'method' => PaymentMethod::MobileMoney, 'paid_at' => now()->subMonths(3), 'operator_reference' => 'RECU-OLD']);
        Payment::factory()->for(Reservation::factory())->create(['amount' => 12000, 'statut' => TransactionStatus::Pending]);

        $this->actingAs($admin)->get(route('admin.paiements.index'))
            ->assertOk()
            ->assertSee('80 000 FCFA')
            ->assertSee('12 000 FCFA')
            ->assertSee('Par moyen de paiement');

        $this->actingAs($admin)->get(route('admin.paiements.index', ['periode' => 'aujourdhui']))
            ->assertSee('RECU-TODAY')->assertDontSee('RECU-OLD');

        $this->actingAs($admin)->get(route('admin.paiements.index', ['moyen' => 'mobile_money']))
            ->assertSee('RECU-OLD')->assertDontSee('RECU-TODAY');
    }

    public function test_detail_is_reserved_to_managers_and_receipt_to_the_guest_too(): void
    {
        $owner = User::factory()->owner()->create();
        $client = User::factory()->create();
        $reservation = Reservation::factory()->confirmed()->for($client)->for(Property::factory()->for($owner, 'owner'))->create();
        $payment = $this->payment(['amount' => 45000], $reservation);

        $this->actingAs($owner)->get(route('admin.paiements.show', $payment))->assertOk()->assertSee($payment->transaction_id)->assertSee($reservation->reference);
        $this->actingAs($client)->get(route('admin.paiements.show', $payment))->assertRedirect(route('client.dashboard'));

        $this->actingAs($client)->get(route('admin.paiements.receipt', $payment))->assertOk()->assertSee('Reçu de paiement')->assertSee('45 000 FCFA');
        $this->actingAs(User::factory()->create())->get(route('admin.paiements.receipt', $payment))->assertForbidden();
        $this->actingAs(User::factory()->owner()->create())->get(route('admin.paiements.show', $payment))->assertForbidden();
    }

    public function test_export_follows_the_filters(): void
    {
        $admin = User::factory()->admin()->create();
        $cash = $this->payment(['method' => PaymentMethod::Cash]);
        $card = $this->payment(['method' => PaymentMethod::Card]);

        $rows = $this->excelRows($this->actingAs($admin)->get(route('admin.paiements.export', ['moyen' => 'cash'])));

        $this->assertSame(['Transaction', 'Date', 'Statut'], array_slice($rows[0], 0, 3));
        $this->assertCount(2, $rows, 'En-tête + le seul paiement en espèces');
        $this->assertSame($cash->transaction_id, $rows[1][0]);
        $this->assertSame($cash->amount, (int) $rows[1][6]);
        $this->assertStringNotContainsString($card->transaction_id, json_encode($rows));
    }

    public function test_partial_then_full_refund_of_a_payment(): void
    {
        Notification::fake();
        $owner = User::factory()->owner()->create();
        $reservation = Reservation::factory()->confirmed()->for(Property::factory()->for($owner, 'owner'))
            ->create(['total_amount' => 100000, 'amount_paid' => 100000, 'payment_state' => PaymentState::Paid]);
        $payment = $this->payment(['amount' => 100000], $reservation);

        // Motif obligatoire, montant plafonné
        $this->actingAs($owner)->patch(route('admin.paiements.refund', $payment), ['montant' => '20 000'])->assertSessionHasErrors('motif');
        $this->actingAs($owner)->patch(route('admin.paiements.refund', $payment), ['montant' => 150000, 'motif' => 'Trop perçu'])->assertSessionHasErrors('montant');

        $this->actingAs($owner)->patch(route('admin.paiements.refund', $payment), ['montant' => '20 000', 'motif' => 'Séjour écourté d’une nuit'])
            ->assertSessionHas('success');

        $payment->refresh();
        $reservation->refresh();
        $this->assertSame(20000, $payment->refunded_amount);
        $this->assertSame(TransactionStatus::Accepted, $payment->statut);
        $this->assertTrue($payment->isPartiallyRefunded());
        $this->assertSame(80000, $reservation->amount_paid);
        $this->assertSame(PaymentState::Partial, $reservation->payment_state);
        Notification::assertSentTo($reservation->user, ReservationUpdated::class, fn (ReservationUpdated $n) => $n->amount === 20000);

        $this->actingAs($owner)->patch(route('admin.paiements.refund', $payment), ['montant' => 80000, 'motif' => 'Annulation amiable']);

        $this->assertSame(TransactionStatus::Refunded, $payment->fresh()->statut);
        $this->assertSame(PaymentState::Refunded, $reservation->fresh()->payment_state);
        $this->assertSame(0, $payment->fresh()->refundableAmount());
    }

    public function test_refunded_amounts_are_excluded_from_revenue(): void
    {
        $admin = User::factory()->admin()->create();
        $this->payment(['amount' => 60000, 'refunded_amount' => 15000]);

        $this->actingAs($admin)->get(route('admin.paiements.index'))
            ->assertSee('45 000 FCFA')
            ->assertSee('15 000 FCFA');
    }
}
