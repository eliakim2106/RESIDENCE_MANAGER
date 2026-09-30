<?php

namespace Tests\Feature\Admin;

use App\Enums\PaymentState;
use App\Enums\ReservationStatus;
use App\Enums\TransactionStatus;
use App\Models\Payment;
use App\Models\Property;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Réservations et paiements : visibilité selon le rôle, changements de statut, encaissements.
 */
class ReservationTest extends TestCase
{
    use RefreshDatabase;

    /*
    |--------------------------------------------------------------------------
    | VISIBILITÉ
    |--------------------------------------------------------------------------
    */

    public function test_each_role_only_sees_its_reservations(): void
    {
        $owner = User::factory()->owner()->create();
        $client = User::factory()->create();
        $mine = Reservation::factory()->for(Property::factory()->for($owner, 'owner'))->for($client)->create();
        $other = Reservation::factory()->create();

        $this->actingAs(User::factory()->admin()->create())->get(route('admin.reservations.index'))
            ->assertOk()->assertSee($mine->reference)->assertSee($other->reference);

        $this->actingAs($owner)->get(route('admin.reservations.index'))
            ->assertOk()->assertSee($mine->reference)->assertDontSee($other->reference);

        $this->actingAs($client)->get(route('admin.reservations.index'))
            ->assertOk()->assertSee('Mes réservations')->assertSee($mine->reference)->assertDontSee($other->reference);

        $this->actingAs($owner)->get(route('admin.reservations.show', $mine))->assertOk();
        $this->actingAs($client)->get(route('admin.reservations.show', $mine))->assertOk()->assertDontSee('Notes internes');
        $this->actingAs($owner)->get(route('admin.reservations.show', $other))->assertForbidden();
        $this->actingAs($client)->get(route('admin.reservations.show', $other))->assertForbidden();
    }

    public function test_dashboards_link_to_reservations(): void
    {
        $owner = User::factory()->owner()->create();
        $client = User::factory()->create();
        $reservation = Reservation::factory()->for(Property::factory()->for($owner, 'owner'))->for($client)->create();
        Reservation::factory()->cancelled()->for($reservation->property)->create();

        foreach ([User::factory()->admin()->create(), $owner, $client] as $user) {
            $this->actingAs($user)->get(route('dashboard'))
                ->assertOk()
                ->assertSee(route('admin.reservations.show', $reservation));
        }
    }

    public function test_list_filters_by_status_tab_and_search(): void
    {
        $admin = User::factory()->admin()->create();
        $pending = Reservation::factory()->create(['guest_name' => 'Awa Koné']);
        $confirmed = Reservation::factory()->confirmed()->create(['guest_name' => 'Yao Kouassi']);

        $this->actingAs($admin)->get(route('admin.reservations.index', ['statut' => 'en-attente']))
            ->assertSee($pending->reference)->assertDontSee($confirmed->reference);

        $this->actingAs($admin)->get(route('admin.reservations.index', ['search' => 'Kouassi']))
            ->assertSee($confirmed->reference)->assertDontSee($pending->reference);
    }

    public function test_payments_page_is_reserved_to_managers_and_scoped(): void
    {
        $owner = User::factory()->owner()->create();
        Payment::factory()->for(Reservation::factory()->for(Property::factory()->for($owner, 'owner')))->create(['operator_reference' => 'RECU-MINE']);
        Payment::factory()->create(['operator_reference' => 'RECU-OTHER']);

        $this->actingAs($owner)->get(route('admin.paiements.index'))
            ->assertOk()->assertSee('RECU-MINE')->assertDontSee('RECU-OTHER');

        $this->actingAs(User::factory()->admin()->create())->get(route('admin.paiements.index'))
            ->assertOk()->assertSee('RECU-MINE')->assertSee('RECU-OTHER');

        $this->actingAs(User::factory()->create())->get(route('admin.paiements.index'))->assertForbidden();
    }

    /*
    |--------------------------------------------------------------------------
    | ACTIONS DU PROPRIÉTAIRE
    |--------------------------------------------------------------------------
    */

    public function test_owner_confirms_records_payment_and_writes_notes(): void
    {
        $owner = User::factory()->owner()->create();
        $reservation = Reservation::factory()->for(Property::factory()->for($owner, 'owner'))->create(['total_amount' => 50000]);

        $this->actingAs($owner)->patch(route('admin.reservations.confirm', $reservation))->assertSessionHas('success');
        $this->assertSame(ReservationStatus::Confirmed, $reservation->fresh()->statut);

        $this->actingAs($owner)->post(route('admin.reservations.payments.store', $reservation), [
            'montant' => '20 000',
            'moyen' => 'cash',
            'reference' => 'RECU-042',
        ])->assertSessionHas('success');

        $reservation->refresh();
        $this->assertSame(20000, $reservation->amount_paid);
        $this->assertSame(PaymentState::Partial, $reservation->payment_state);
        $this->assertDatabaseHas('payments', ['reservation_id' => $reservation->id, 'amount' => 20000, 'statut' => TransactionStatus::Accepted->value, 'operator_reference' => 'RECU-042']);

        // Le solde ne peut pas être dépassé
        $this->actingAs($owner)->post(route('admin.reservations.payments.store', $reservation), ['montant' => 40000, 'moyen' => 'cash'])
            ->assertSessionHas('error');
        $this->assertSame(20000, $reservation->fresh()->amount_paid);

        $this->actingAs($owner)->post(route('admin.reservations.payments.store', $reservation), ['montant' => 30000, 'moyen' => 'mobile_money'])
            ->assertSessionHas('success');
        $this->assertSame(PaymentState::Paid, $reservation->fresh()->payment_state);

        $this->actingAs($owner)->patch(route('admin.reservations.notes', $reservation), ['notes' => 'Client fidèle'])->assertSessionHas('success');
        $this->assertSame('Client fidèle', $reservation->fresh()->owner_notes);
    }

    public function test_stay_can_only_be_completed_after_check_out(): void
    {
        $owner = User::factory()->owner()->create();
        $property = Property::factory()->for($owner, 'owner')->create();
        $upcoming = Reservation::factory()->confirmed()->for($property)->create();
        $past = Reservation::factory()->confirmed()->for($property)->create(['check_in' => now()->subDays(5), 'check_out' => now()->subDays(2)]);

        $this->actingAs($owner)->patch(route('admin.reservations.complete', $upcoming))->assertSessionHas('error');
        $this->assertSame(ReservationStatus::Confirmed, $upcoming->fresh()->statut);

        $this->actingAs($owner)->patch(route('admin.reservations.complete', $past))->assertSessionHas('success');
        $this->assertSame(ReservationStatus::Completed, $past->fresh()->statut);
    }

    public function test_super_admin_validates_refuses_and_cancels_any_reservation(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $toValidate = Reservation::factory()->pending()->create();
        $toRefuse = Reservation::factory()->pending()->create();
        $toCancel = Reservation::factory()->confirmed()->create();

        $this->actingAs($superAdmin)->get(route('admin.reservations.show', $toRefuse))
            ->assertOk()->assertSee('Valider la réservation')->assertSee('Refuser la réservation');

        $this->actingAs($superAdmin)->patch(route('admin.reservations.confirm', $toValidate))->assertSessionHas('success', 'Réservation validée.');
        $this->assertSame(ReservationStatus::Confirmed, $toValidate->fresh()->statut);

        $this->actingAs($superAdmin)->patch(route('admin.reservations.cancel', $toRefuse), ['motif' => 'Établissement complet'])
            ->assertSessionHas('success', 'Réservation refusée.');
        $this->assertSame(ReservationStatus::Cancelled, $toRefuse->fresh()->statut);

        $this->actingAs($superAdmin)->get(route('admin.reservations.show', $toCancel))->assertOk()->assertSee('Annuler la réservation');
        $this->actingAs($superAdmin)->patch(route('admin.reservations.cancel', $toCancel))->assertSessionHas('success', 'Réservation annulée.');
        $this->assertSame(ReservationStatus::Cancelled, $toCancel->fresh()->statut);
    }

    public function test_owner_cannot_act_on_another_owner_reservation(): void
    {
        $reservation = Reservation::factory()->create();

        $this->actingAs(User::factory()->owner()->create())
            ->patch(route('admin.reservations.confirm', $reservation))
            ->assertForbidden();

        $this->assertSame(ReservationStatus::Pending, $reservation->fresh()->statut);
    }

    /*
    |--------------------------------------------------------------------------
    | ACTIONS DU CLIENT
    |--------------------------------------------------------------------------
    */

    public function test_client_cancels_an_upcoming_reservation_but_cannot_confirm_it(): void
    {
        $client = User::factory()->create();
        $reservation = Reservation::factory()->confirmed()->for($client)->create(['check_in' => now()->addDays(10), 'check_out' => now()->addDays(12)]);

        $this->actingAs($client)->patch(route('admin.reservations.confirm', $reservation))->assertForbidden();

        $this->actingAs($client)->patch(route('admin.reservations.cancel', $reservation), ['motif' => 'Changement de programme'])
            ->assertSessionHas('success');

        $reservation->refresh();
        $this->assertSame(ReservationStatus::Cancelled, $reservation->statut);
        $this->assertSame('Changement de programme', $reservation->cancellation_reason);
        $this->assertNotNull($reservation->cancelled_at);
    }

    public function test_client_cannot_cancel_a_stay_that_has_started(): void
    {
        $client = User::factory()->create();
        $reservation = Reservation::factory()->confirmed()->for($client)->create(['check_in' => now()->subDay(), 'check_out' => now()->addDays(2)]);

        $this->actingAs($client)->patch(route('admin.reservations.cancel', $reservation))->assertForbidden();
        $this->assertSame(ReservationStatus::Confirmed, $reservation->fresh()->statut);
    }
}
