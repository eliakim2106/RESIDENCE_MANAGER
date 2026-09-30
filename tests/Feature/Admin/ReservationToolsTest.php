<?php

namespace Tests\Feature\Admin;

use App\Enums\PaymentState;
use App\Enums\ReservationStatus;
use App\Enums\TransactionStatus;
use App\Models\Payment;
use App\Models\Property;
use App\Models\Reservation;
use App\Models\ReservationUnit;
use App\Models\Unit;
use App\Models\User;
use App\Notifications\ReservationUpdated;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Outils de la page Réservations : filtres par date et tri, export, bon, calendrier, emails, remboursement.
 */
class ReservationToolsTest extends TestCase
{
    use RefreshDatabase;

    /*
    |--------------------------------------------------------------------------
    | FILTRES ET TRI
    |--------------------------------------------------------------------------
    */

    public function test_list_filters_arrivals_by_period(): void
    {
        $admin = User::factory()->admin()->create();
        $today = Reservation::factory()->create(['check_in' => today(), 'check_out' => today()->addDays(2)]);
        $later = Reservation::factory()->create(['check_in' => today()->addMonths(2), 'check_out' => today()->addMonths(2)->addDays(3)]);

        $this->actingAs($admin)->get(route('admin.reservations.index', ['periode' => 'aujourdhui']))
            ->assertOk()->assertSee($today->reference)->assertDontSee($later->reference);

        $from = today()->addMonths(2)->subDay()->toDateString();
        $to = today()->addMonths(2)->addDay()->toDateString();

        $this->actingAs($admin)->get(route('admin.reservations.index', ['du' => $from, 'au' => $to]))
            ->assertOk()->assertSee($later->reference)->assertDontSee($today->reference)->assertSee('Effacer les filtres');
    }

    public function test_list_sorts_by_amount(): void
    {
        $admin = User::factory()->admin()->create();
        $small = Reservation::factory()->create(['total_amount' => 10000]);
        $large = Reservation::factory()->create(['total_amount' => 900000]);

        $this->actingAs($admin)->get(route('admin.reservations.index', ['tri' => 'montant', 'ordre' => 'desc']))
            ->assertSeeInOrder([$large->reference, $small->reference]);

        $this->actingAs($admin)->get(route('admin.reservations.index', ['tri' => 'montant', 'ordre' => 'asc']))
            ->assertSeeInOrder([$small->reference, $large->reference]);
    }

    /*
    |--------------------------------------------------------------------------
    | EXPORT ET BON
    |--------------------------------------------------------------------------
    */

    public function test_export_contains_only_the_filtered_reservations(): void
    {
        $owner = User::factory()->owner()->create();
        $mine = Reservation::factory()->for(Property::factory()->for($owner, 'owner'))->create(['guest_name' => 'Awa Koné']);
        $other = Reservation::factory()->create();

        $response = $this->actingAs($owner)->get(route('admin.reservations.export'));

        $response->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $csv = $response->streamedContent();

        $this->assertStringStartsWith("\xEF\xBB\xBF", $csv);
        $this->assertStringContainsString('Référence;', $csv);
        $this->assertStringContainsString($mine->reference.';', $csv);
        $this->assertStringContainsString('Awa Koné', $csv);
        $this->assertStringNotContainsString($other->reference, $csv);
    }

    public function test_voucher_is_printable_by_the_guest_but_not_by_strangers(): void
    {
        $client = User::factory()->create();
        $reservation = Reservation::factory()->confirmed()->for($client)->create();

        $this->actingAs($client)->get(route('admin.reservations.voucher', $reservation))
            ->assertOk()->assertSee('Bon de réservation')->assertSee($reservation->reference)->assertSee('Imprimer / Enregistrer en PDF');

        $this->actingAs(User::factory()->create())->get(route('admin.reservations.voucher', $reservation))->assertForbidden();
    }

    /*
    |--------------------------------------------------------------------------
    | CALENDRIER
    |--------------------------------------------------------------------------
    */

    public function test_calendar_shows_nights_occupied_by_a_stay(): void
    {
        $owner = User::factory()->owner()->create();
        $property = Property::factory()->for($owner, 'owner')->create();
        $unit = Unit::factory()->for($property)->create(['name' => 'Suite Lagune', 'quantity' => 1]);
        $checkIn = today()->startOfMonth()->addDays(9);
        $reservation = Reservation::factory()->confirmed()->for($property)->create([
            'guest_name' => 'Yao Kouassi',
            'check_in' => $checkIn,
            'check_out' => $checkIn->copy()->addDays(2),
            'nights' => 2,
        ]);
        ReservationUnit::factory()->for($reservation)->for($unit)->create(['quantity' => 1]);

        $response = $this->actingAs($owner)->get(route('admin.reservations.calendar', ['mois' => $checkIn->format('Y-m')]));

        $response->assertOk()->assertSee('Suite Lagune')->assertSee('YK')->assertSee(route('admin.reservations.show', $reservation));
        $this->assertSame(2, substr_count($response->getContent(), 'occ-cell state-full'));

        // Réservé aux gestionnaires
        $this->actingAs(User::factory()->create())->get(route('admin.reservations.calendar'))->assertForbidden();
    }

    /*
    |--------------------------------------------------------------------------
    | EMAILS ET REMBOURSEMENT
    |--------------------------------------------------------------------------
    */

    public function test_guest_is_emailed_when_validated_or_refused(): void
    {
        Notification::fake();
        $admin = User::factory()->admin()->create();
        $client = User::factory()->create();
        $validated = Reservation::factory()->pending()->for($client)->create();
        $refused = Reservation::factory()->pending()->for($client)->create();

        $this->actingAs($admin)->patch(route('admin.reservations.confirm', $validated));
        $this->actingAs($admin)->patch(route('admin.reservations.cancel', $refused), ['motif' => 'Complet à ces dates']);

        Notification::assertSentTo($client, ReservationUpdated::class, fn (ReservationUpdated $n, array $channels) => $n->event === ReservationUpdated::CONFIRMED && in_array('mail', $channels, true));
        Notification::assertSentTo($client, ReservationUpdated::class, function (ReservationUpdated $n) {
            return $n->event === ReservationUpdated::REFUSED
                && str_contains(implode(' ', $n->toMail($n->reservation->user)->introLines), 'Complet à ces dates');
        });
    }

    public function test_owner_is_notified_when_the_guest_cancels(): void
    {
        Notification::fake();
        $owner = User::factory()->owner()->create();
        $client = User::factory()->create();
        $reservation = Reservation::factory()->confirmed()->for($client)->for(Property::factory()->for($owner, 'owner'))
            ->create(['check_in' => now()->addDays(10), 'check_out' => now()->addDays(12)]);

        $this->actingAs($client)->patch(route('admin.reservations.cancel', $reservation))->assertSessionHas('success');

        Notification::assertSentTo($owner, ReservationUpdated::class, fn (ReservationUpdated $n) => $n->event === ReservationUpdated::CANCELLED_BY_GUEST);
        Notification::assertNotSentTo($client, ReservationUpdated::class);
    }

    public function test_cancellation_with_refund_marks_payments_as_refunded(): void
    {
        Notification::fake();
        $admin = User::factory()->admin()->create();
        $client = User::factory()->create();
        $reservation = Reservation::factory()->confirmed()->for($client)->create(['total_amount' => 80000, 'amount_paid' => 80000]);
        $payment = Payment::factory()->accepted()->for($reservation)->create(['amount' => 80000]);

        $this->actingAs($admin)->patch(route('admin.reservations.cancel', $reservation), ['motif' => 'Dégât des eaux', 'rembourser' => '1'])
            ->assertSessionHas('success', 'Réservation annulée. Le remboursement est enregistré.');

        $reservation->refresh();
        $this->assertSame(ReservationStatus::Cancelled, $reservation->statut);
        $this->assertSame(PaymentState::Refunded, $reservation->payment_state);
        $this->assertSame(0, $reservation->amount_paid);
        $this->assertSame(TransactionStatus::Refunded, $payment->fresh()->statut);
        $this->assertNotNull($payment->fresh()->refunded_at);
        Notification::assertSentTo($client, ReservationUpdated::class, fn (ReservationUpdated $n) => $n->event === ReservationUpdated::REFUNDED && $n->amount === 80000);
    }

    public function test_refund_after_cancellation_and_its_rules(): void
    {
        $owner = User::factory()->owner()->create();
        $property = Property::factory()->for($owner, 'owner')->create();
        $cancelled = Reservation::factory()->cancelled()->for($property)->create(['amount_paid' => 30000, 'payment_state' => PaymentState::Partial]);
        Payment::factory()->accepted()->for($cancelled)->create(['amount' => 30000]);
        $confirmed = Reservation::factory()->confirmed()->for($property)->create();

        $this->actingAs($owner)->patch(route('admin.reservations.refund', $confirmed))->assertSessionHas('error');
        $this->actingAs($owner)->patch(route('admin.reservations.refund', $cancelled))->assertSessionHas('success');
        $this->assertSame(PaymentState::Refunded, $cancelled->fresh()->payment_state);

        // Un client ne se rembourse pas lui-même
        $this->actingAs($cancelled->user)->patch(route('admin.reservations.refund', $cancelled))->assertForbidden();
    }
}
