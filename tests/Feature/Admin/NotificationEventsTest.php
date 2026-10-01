<?php

namespace Tests\Feature\Admin;

use App\Enums\BillingCycle;
use App\Enums\PaymentState;
use App\Enums\UserStatus;
use App\Models\Property;
use App\Models\Reservation;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Notifications\AdminSubscriptionAlert;
use App\Notifications\ArrivalsReminder;
use App\Notifications\ReservationUpdated;
use App\Notifications\SubscriptionUpdated;
use App\Services\ReservationWorkflow;
use App\Services\SubscriptionManager;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\ChannelManager;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Notifications ajoutées : nouvelle réservation, arrivées du lendemain, rappel d'échéance,
 * alertes aux administrateurs (suspension, facture réglée en ligne).
 */
class NotificationEventsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
    }

    public function test_owner_is_told_about_a_new_reservation(): void
    {
        $owner = User::factory()->owner()->create();
        $reservation = Reservation::factory()->pending()->for(Property::factory()->for($owner, 'owner'))->create();

        app(ReservationWorkflow::class)->announce($reservation);

        Notification::assertSentTo($owner, ReservationUpdated::class, function (ReservationUpdated $notification, array $channels) use ($owner) {
            $data = $notification->toArray($owner);

            return $notification->event === ReservationUpdated::NEW_FOR_OWNER
                && $channels === ['mail', 'database']
                && str_contains($data['message'], 'à valider')
                && $data['tone'] === 'warning';
        });
    }

    public function test_owners_receive_tomorrows_arrivals_once(): void
    {
        $tomorrow = CarbonImmutable::tomorrow();
        $first = User::factory()->owner()->create();
        $second = User::factory()->owner()->create();
        $quiet = User::factory()->owner()->create();
        $propertyA = Property::factory()->for($first, 'owner')->create();
        $propertyB = Property::factory()->for($second, 'owner')->create();

        $arrive = fn (Property $property, string $state, CarbonImmutable $day) => Reservation::factory()->{$state}()->for($property)
            ->create(['check_in' => $day, 'check_out' => $day->addDays(2), 'nights' => 2]);

        $arrive($propertyA, 'confirmed', $tomorrow);
        $arrive($propertyA, 'confirmed', $tomorrow);
        $arrive($propertyB, 'confirmed', $tomorrow);
        $arrive($propertyB, 'pending', $tomorrow);              // pas encore validée : ignorée
        $arrive($propertyB, 'confirmed', $tomorrow->addDay());  // après-demain : ignorée
        Property::factory()->for($quiet, 'owner')->create();

        $this->assertSame(2, app(ReservationWorkflow::class)->remindArrivals());
        $this->assertSame(0, app(ReservationWorkflow::class)->remindArrivals(), 'Un seul rappel par jour');

        Notification::assertSentTo($first, ArrivalsReminder::class, fn (ArrivalsReminder $n) => $n->reservations->count() === 2
            && str_starts_with($n->toArray($first)['message'], '2 arrivées demain'));
        Notification::assertSentTo($second, ArrivalsReminder::class, fn (ArrivalsReminder $n) => $n->reservations->count() === 1);
        Notification::assertNotSentTo($quiet, ArrivalsReminder::class);
    }

    public function test_owner_gets_a_reminder_before_the_due_date_then_admins_are_alerted_on_suspension(): void
    {
        $owner = User::factory()->owner()->create();
        $admin = User::factory()->admin()->create();
        $suspendedAdmin = User::factory()->admin()->create(['statut' => UserStatus::Suspended]);
        $plan = SubscriptionPlan::factory()->create(['trial_days' => 0]);
        $subscription = app(SubscriptionManager::class)->subscribe($owner, $plan, BillingCycle::Monthly);
        $invoice = $subscription->invoices()->sole();
        $manager = app(SubscriptionManager::class);

        // Trop tôt : pas de rappel
        $this->assertSame(0, $manager->process(CarbonImmutable::parse($invoice->due_on)->subDays(5))['reminded']);

        // 3 jours avant l'échéance : un rappel, une seule fois
        $this->assertSame(1, $manager->process(CarbonImmutable::parse($invoice->due_on)->subDays(3))['reminded']);
        $this->assertSame(0, $manager->process(CarbonImmutable::parse($invoice->due_on)->subDays(2))['reminded']);
        $this->assertNotNull($invoice->fresh()->reminder_sent_at);
        $this->assertCount(1, Notification::sent($owner, SubscriptionUpdated::class)->filter(fn ($n) => $n->event === SubscriptionUpdated::INVOICE_REMINDER));

        // Échéance dépassée : suspension, administrateurs actifs prévenus
        $manager->process(CarbonImmutable::parse($invoice->due_on)->addDay());

        Notification::assertSentTo($admin, AdminSubscriptionAlert::class, fn (AdminSubscriptionAlert $n) => $n->event === AdminSubscriptionAlert::SUSPENDED
            && str_contains($n->toArray($admin)['message'], $owner->name));
        Notification::assertNotSentTo($suspendedAdmin, AdminSubscriptionAlert::class);
    }

    public function test_admins_are_alerted_when_an_invoice_is_paid_online(): void
    {
        config(['payments.gateway' => 'fedapay', 'services.fedapay.secret_key' => 'sk_sandbox_test']);
        Http::fake([
            'sandbox-api.fedapay.com/v1/transactions/77/token' => Http::response(['token' => 't', 'url' => 'https://sandbox-process.fedapay.com/t']),
            'sandbox-api.fedapay.com/v1/transactions/77' => Http::response(['v1/transaction' => ['id' => 77, 'status' => 'approved', 'amount' => 15000, 'mode' => 'mtn_open']]),
            'sandbox-api.fedapay.com/v1/transactions' => Http::response(['v1/transaction' => ['id' => 77, 'status' => 'pending']]),
        ]);

        $owner = User::factory()->owner()->create();
        $admin = User::factory()->superAdmin()->create();
        $plan = SubscriptionPlan::factory()->create(['trial_days' => 0, 'monthly_price' => 15000]);
        $invoice = app(SubscriptionManager::class)->subscribe($owner, $plan, BillingCycle::Monthly)->invoices()->sole();

        $this->actingAs($owner)->post(route('admin.abonnement.pay-online', $invoice));
        $this->postJson(route('paiements.notify', 'fedapay'), ['entity' => ['id' => 77]])->assertOk();
        $this->postJson(route('paiements.notify', 'fedapay'), ['entity' => ['id' => 77]])->assertOk();

        Notification::assertSentToTimes($admin, AdminSubscriptionAlert::class, 1);
        Notification::assertSentTo($admin, AdminSubscriptionAlert::class, fn (AdminSubscriptionAlert $n) => $n->event === AdminSubscriptionAlert::INVOICE_PAID_ONLINE
            && str_contains($n->toArray($admin)['message'], $invoice->number));
    }

    public function test_new_notifications_render_in_the_bell_menu(): void
    {
        // Envoi réel (enregistrement en base) pour lire la notification dans le menu cloche
        Notification::swap(new ChannelManager($this->app));

        $owner = User::factory()->owner()->create();
        $reservation = Reservation::factory()->confirmed()->for(Property::factory()->for($owner, 'owner'))
            ->create(['amount_paid' => 0, 'payment_state' => PaymentState::Unpaid]);

        app(ReservationWorkflow::class)->announce($reservation);

        $this->actingAs($owner)->getJson(route('admin.notifications.feed'))
            ->assertJson(['count' => 1])
            ->assertJsonPath('html', fn (string $html) => str_contains($html, 'Nouvelle réservation de') && str_contains($html, 'fa-calendar-plus'));
    }
}
