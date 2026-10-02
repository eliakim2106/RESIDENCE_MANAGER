<?php

namespace Tests\Feature;

use App\Enums\PaymentState;
use App\Enums\ReservationStatus;
use App\Enums\TransactionStatus;
use App\Models\Availability;
use App\Models\Payment;
use App\Models\Property;
use App\Models\PropertyImage;
use App\Models\Reservation;
use App\Models\ReservationUnit;
use App\Models\Unit;
use App\Models\User;
use App\Notifications\ReservationUpdated;
use App\Services\BookingEngine;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Fiche d'une résidence, prix d'un séjour et réservation en ligne (visiteur, client, autres comptes).
 */
class BookingTest extends TestCase
{
    use RefreshDatabase;

    private Property $residence;

    private Unit $unit;

    private CarbonImmutable $arrival;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->residence = Property::factory()->create(['name' => 'Résidence Lagune', 'check_in_from' => '14:00']);
        $this->unit = Unit::factory()->for($this->residence)->create([
            'name' => 'Suite Lagune',
            'quantity' => 2,
            'max_adults' => 2,
            'max_children' => 1,
            'base_price' => 40000,
            'promo_price' => null,
            'weekend_price' => null,
            'cleaning_fee' => 5000,
            'min_nights' => 1,
        ]);

        // Un lundi, pour éviter les prix du week-end
        $this->arrival = CarbonImmutable::today()->addWeeks(2)->startOfWeek();
    }

    /**
     * @return array<string, mixed>
     */
    private function stay(int $quantity = 1, int $nights = 3): array
    {
        return [
            'arrivee' => $this->arrival->toDateString(),
            'depart' => $this->arrival->addDays($nights)->toDateString(),
            'adultes' => 2,
            'unites' => [$this->unit->id => $quantity],
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | FICHE ET PRIX
    |--------------------------------------------------------------------------
    */

    public function test_residence_page_shows_prices_for_the_chosen_dates(): void
    {
        $this->get(route('residences.show', $this->residence))
            ->assertOk()
            ->assertSee('Résidence Lagune')
            ->assertSee('Suite Lagune')
            ->assertSee('Voir les disponibilités');

        $this->get(route('residences.show', [$this->residence, 'arrivee' => $this->arrival->toDateString(), 'depart' => $this->arrival->addDays(3)->toDateString()]))
            ->assertOk()
            ->assertSee('120 000 FCFA')
            ->assertSee('name="unites['.$this->unit->id.']"', false);
    }

    public function test_a_shared_link_shows_the_name_and_the_cover_photo(): void
    {
        PropertyImage::factory()->for($this->residence)->create(['path' => 'properties/1/gallery/piscine.jpg', 'is_cover' => false]);
        PropertyImage::factory()->for($this->residence)->create(['path' => 'properties/1/gallery/facade.jpg', 'is_cover' => true]);
        $this->residence->update(['short_description' => 'Résidence calme au bord de la lagune.', 'meta_description' => null]);

        $html = $this->get(route('residences.show', [$this->residence, 'arrivee' => $this->arrival->toDateString()]))->assertOk()->getContent();

        $this->assertStringContainsString('<meta property="og:title" content="Résidence Lagune · '.e($this->residence->city->name).'">', $html);
        $this->assertStringContainsString('<meta property="og:description" content="Résidence calme au bord de la lagune.">', $html);
        $this->assertMatchesRegularExpression('#<meta property="og:image" content="https?://[^"]+/storage/properties/1/gallery/facade\.jpg">#', $html);
        $this->assertStringContainsString('<meta property="og:url" content="'.route('residences.show', $this->residence).'">', $html);
        $this->assertStringContainsString('<meta name="twitter:card" content="summary_large_image">', $html);
    }

    public function test_night_price_follows_calendar_then_weekend_then_base(): void
    {
        $this->unit->update(['weekend_price' => 50000]);
        $monday = $this->arrival;
        Availability::factory()->for($this->unit)->create(['date' => $monday->addDay(), 'price' => 70000, 'is_closed' => false, 'blocked_quantity' => 0]);

        // Lundi → lundi : lundi (base), mardi (calendrier), mer, jeu (base), ven, sam (week-end), dim (base)
        $quote = app(BookingEngine::class)->quote($this->unit->fresh(), $monday, $monday->addWeek());

        $this->assertSame(7, $quote['nights']);
        $this->assertSame(40000 * 4 + 70000 + 50000 * 2, $quote['subtotal']);
    }

    public function test_unavailable_residence_page_is_hidden_from_visitors(): void
    {
        $draft = Property::factory()->draft()->create();

        $this->get(route('residences.show', $draft))->assertNotFound();
        $this->actingAs($draft->owner)->get(route('residences.show', $draft))->assertOk()->assertSee('Aperçu');
    }

    public function test_old_demo_page_redirects_to_the_listing(): void
    {
        $this->get('/residences/details')->assertRedirect(route('residences.index'));
    }

    /*
    |--------------------------------------------------------------------------
    | PARCOURS DE RÉSERVATION
    |--------------------------------------------------------------------------
    */

    public function test_visitor_is_sent_to_sign_up_and_comes_back_to_the_summary(): void
    {
        $this->get(route('residences.checkout', [$this->residence, ...$this->stay()]))
            ->assertRedirect(route('register.client'))
            ->assertSessionHas('info');

        $this->assertStringContainsString('/reserver/'.$this->residence->slug, session('url.intended'));
    }

    public function test_client_books_a_stay(): void
    {
        Notification::fake();
        $client = User::factory()->create();

        $this->actingAs($client)->get(route('residences.checkout', [$this->residence, ...$this->stay(2)]))
            ->assertOk()
            ->assertSee('Finaliser ma réservation')
            ->assertSee('2 × Suite Lagune')
            ->assertSee('250 000 FCFA')
            // Le champ téléphone garde son nom malgré les variables de la vue (régression : il prenait le nom « adultes »)
            ->assertSee('name="telephone"', false)
            ->assertDontSee('name="adultes" value="'.$client->phone.'"', false);

        $this->actingAs($client)->post(route('residences.book', $this->residence), [
            ...$this->stay(2),
            'nom' => 'Awa Koné',
            'email' => 'AWA@example.com',
            'indicatif_telephone' => '+225',
            'telephone' => '07 01 02 03 04',
            'heure_arrivee' => '15:30',
            'conditions' => '1',
        ])->assertRedirect();

        $reservation = Reservation::sole();
        $this->assertSame(ReservationStatus::Pending, $reservation->statut);
        $this->assertSame(PaymentState::Unpaid, $reservation->payment_state);
        $this->assertSame(240000, $reservation->subtotal);
        $this->assertSame(10000, $reservation->cleaning_fee);
        $this->assertSame(250000, $reservation->total_amount);
        $this->assertSame('awa@example.com', $reservation->guest_email);
        $this->assertSame('0701020304', $reservation->guest_phone);
        $this->assertNotNull($reservation->expires_at);
        $this->assertSame(2, ReservationUnit::sole()->quantity);
        $this->assertCount(3, ReservationUnit::sole()->nightly_prices);

        Notification::assertSentTo($this->residence->owner, ReservationUpdated::class);
    }

    public function test_booking_is_refused_when_the_units_are_no_longer_available(): void
    {
        $client = User::factory()->create();
        $other = Reservation::factory()->confirmed()->for($this->residence)->create([
            'check_in' => $this->arrival,
            'check_out' => $this->arrival->addDays(3),
        ]);
        ReservationUnit::factory()->for($other)->for($this->unit)->create(['quantity' => 2]);

        $this->actingAs($client)->post(route('residences.book', $this->residence), [
            ...$this->stay(),
            'nom' => 'Awa Koné',
            'email' => 'awa@example.com',
            'indicatif_telephone' => '+225',
            'telephone' => '0701020304',
            'conditions' => '1',
        ])->assertSessionHas('error');

        $this->assertSame(1, Reservation::count());
    }

    public function test_booking_requires_the_conditions_and_enough_capacity(): void
    {
        $client = User::factory()->create();
        $payload = [...$this->stay(), 'nom' => 'Awa', 'email' => 'awa@example.com', 'indicatif_telephone' => '+225', 'telephone' => '0701020304'];

        $this->actingAs($client)->post(route('residences.book', $this->residence), $payload)->assertSessionHasErrors('conditions');

        // 5 voyageurs pour une suite de 3 places
        $this->actingAs($client)->post(route('residences.book', $this->residence), [...$payload, 'adultes' => 5, 'conditions' => '1'])
            ->assertSessionHas('error');

        $this->assertSame(0, Reservation::count());
    }

    public function test_owner_account_cannot_book(): void
    {
        $this->actingAs($this->residence->owner)->get(route('residences.checkout', [$this->residence, ...$this->stay()]))
            ->assertRedirect(route('residences.show', $this->residence))
            ->assertSessionHas('error');
    }

    public function test_emails_wait_in_the_queue_and_never_block_a_booking(): void
    {
        // Serveur d'emails injoignable et vraie file d'attente (base de données)
        config(['queue.default' => 'database', 'mail.default' => 'smtp', 'mail.mailers.smtp.host' => 'smtp.invalid', 'mail.mailers.smtp.port' => 1]);
        $client = User::factory()->create();

        $this->actingAs($client)->post(route('residences.book', $this->residence), [
            ...$this->stay(),
            'nom' => 'Awa Koné',
            'email' => 'awa@example.com',
            'indicatif_telephone' => '+225',
            'telephone' => '0701020304',
            'conditions' => '1',
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertSame(1, Reservation::count());
        // Notification de l'application : immédiate ; email au propriétaire : en file d'attente
        $this->assertSame(1, $this->residence->owner->notifications()->count());
        $this->assertSame(1, DB::table('jobs')->count());
        $this->assertStringContainsString('ReservationUpdated', DB::table('jobs')->value('payload'));
    }

    public function test_unanswered_requests_expire_and_paid_ones_are_refunded(): void
    {
        Notification::fake();
        $client = User::factory()->create();
        $expired = Reservation::factory()->pending()->for($client)->for($this->residence)->create(['expires_at' => now()->subHour()]);
        $paid = Reservation::factory()->pending()->for($client)->for($this->residence)->create(['expires_at' => now()->subMinute()]);
        Payment::factory()->for($paid)->create(['amount' => 30000, 'statut' => TransactionStatus::Accepted]);
        $paid->update(['amount_paid' => 30000, 'payment_state' => PaymentState::Partial]);
        $waiting = Reservation::factory()->pending()->for($client)->for($this->residence)->create(['expires_at' => now()->addHour()]);

        $this->artisan('reservations:expire')->expectsOutput('2 demande(s) expirée(s).')->assertSuccessful();

        $this->assertSame(ReservationStatus::Cancelled, $expired->fresh()->statut);
        $this->assertSame(ReservationStatus::Pending, $waiting->fresh()->statut);
        $this->assertSame(PaymentState::Refunded, $paid->fresh()->payment_state);
        $this->assertSame(0, $paid->fresh()->amount_paid);

        Notification::assertSentTo($client, ReservationUpdated::class, fn (ReservationUpdated $notification) => $notification->event === ReservationUpdated::EXPIRED);
        Notification::assertSentTo($this->residence->owner, ReservationUpdated::class, fn (ReservationUpdated $notification) => $notification->event === ReservationUpdated::EXPIRED_FOR_OWNER);
    }

    public function test_past_dates_are_rejected(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('residences.checkout', [$this->residence, 'arrivee' => now()->subDays(3)->toDateString(), 'depart' => now()->subDay()->toDateString(), 'unites' => [$this->unit->id => 1]]))
            ->assertRedirect()
            ->assertSessionHas('error', 'La date d’arrivée est déjà passée.');
    }
}
