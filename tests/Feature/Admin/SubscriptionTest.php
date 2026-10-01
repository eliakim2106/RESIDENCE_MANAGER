<?php

namespace Tests\Feature\Admin;

use App\Enums\BillingCycle;
use App\Enums\InvoiceStatus;
use App\Enums\PaymentMethod;
use App\Enums\PropertyStatus;
use App\Enums\SubscriptionStatus;
use App\Models\Property;
use App\Models\Setting;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\Unit;
use App\Models\User;
use App\Notifications\SubscriptionUpdated;
use App\Services\SubscriptionManager;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Abonnements des propriétaires : essai, factures, paiement, suspension, limites et droits.
 */
class SubscriptionTest extends TestCase
{
    use RefreshDatabase;

    private function requireSubscriptions(): void
    {
        Setting::set(SubscriptionManager::SETTING_REQUIRED, '1', 'abonnements');
    }

    public function test_admin_manages_plans_and_settings(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->post(route('admin.formules.store'), [
            'nom' => 'Essentiel',
            'prix_mensuel' => '15 000',
            'prix_annuel' => '150 000',
            'max_etablissements' => '1',
            'max_unites' => '',
            'jours_essai' => '30',
            'commission' => '0',
            'avantages' => "Calendrier\nExport",
            'statut' => 'actif',
        ])->assertRedirect(route('admin.formules.index'));

        $plan = SubscriptionPlan::sole();
        $this->assertSame(15000, $plan->monthly_price);
        $this->assertNull($plan->max_units);
        $this->assertSame(['Calendrier', 'Export'], $plan->features);
        $this->assertSame(2, $plan->yearlySavingMonths());

        $this->actingAs($admin)->get(route('admin.formules.index'))->assertOk()->assertSee('Essentiel')->assertSee('2 mois offerts');

        $this->actingAs($admin)->put(route('admin.formules.settings'), ['obligatoire' => '1', 'delai_grace' => 10])->assertSessionHas('success');
        $this->assertTrue(SubscriptionManager::required());
        $this->assertSame(10, SubscriptionManager::graceDays());

        $this->actingAs(User::factory()->owner()->create())->get(route('admin.formules.index'))->assertForbidden();
    }

    public function test_owner_starts_a_trial_then_receives_an_invoice_and_pays(): void
    {
        Notification::fake();
        $owner = User::factory()->owner()->create();
        $plan = SubscriptionPlan::factory()->create(['monthly_price' => 20000, 'trial_days' => 14]);

        $this->actingAs($owner)->get(route('admin.abonnement.show'))->assertOk()->assertSee('Commencer l’essai gratuit');
        $this->actingAs($owner)->post(route('admin.abonnement.subscribe'), ['formule' => $plan->id, 'cycle' => 'monthly'])->assertSessionHas('success');

        $subscription = $owner->fresh()->currentSubscription;
        $this->assertSame(SubscriptionStatus::Trial, $subscription->statut);
        Notification::assertSentTo($owner, SubscriptionUpdated::class, fn ($n) => $n->event === SubscriptionUpdated::TRIAL_STARTED);

        // Fin de l'essai : première facture, paiement attendu
        $result = app(SubscriptionManager::class)->process(CarbonImmutable::today()->addDays(14));
        $this->assertSame(1, $result['invoiced']);

        $subscription->refresh();
        $invoice = $subscription->invoices()->sole();
        $this->assertSame(SubscriptionStatus::PastDue, $subscription->statut);
        $this->assertSame(20000, $invoice->amount);
        $this->assertStringStartsWith('ABO-', $invoice->number);

        $this->actingAs($owner)->get(route('admin.abonnement.invoice', $invoice))->assertOk()->assertSee($invoice->number);

        // L'administration enregistre le paiement
        $this->actingAs(User::factory()->admin()->create())
            ->patch(route('admin.abonnements.invoices.pay', $invoice), ['moyen' => 'mobile_money', 'reference' => 'OM-123'])
            ->assertSessionHas('success');

        $this->assertSame(InvoiceStatus::Paid, $invoice->fresh()->statut);
        $this->assertSame(SubscriptionStatus::Active, $subscription->fresh()->statut);
    }

    public function test_unpaid_subscription_is_suspended_and_properties_leave_the_site(): void
    {
        Notification::fake();
        $this->requireSubscriptions();
        $owner = User::factory()->owner()->create();
        $plan = SubscriptionPlan::factory()->create(['trial_days' => 0]);
        $property = Property::factory()->for($owner, 'owner')->create(['statut' => PropertyStatus::Published]);
        Unit::factory()->for($property)->create();

        $subscription = app(SubscriptionManager::class)->subscribe($owner, $plan, BillingCycle::Monthly);
        $this->assertSame(SubscriptionStatus::PastDue, $subscription->statut);

        // Dans le délai de grâce : toujours en ligne
        $this->assertTrue(Property::query()->onSite()->whereKey($property->id)->exists());

        app(SubscriptionManager::class)->process(CarbonImmutable::today()->addDays(SubscriptionManager::graceDays() + 1));

        $this->assertSame(SubscriptionStatus::Suspended, $subscription->fresh()->statut);
        $this->assertFalse(Property::query()->onSite()->whereKey($property->id)->exists());
        $this->get(route('residences.index'))->assertOk()->assertDontSee($property->name);
        Notification::assertSentTo($owner, SubscriptionUpdated::class, fn ($n) => $n->event === SubscriptionUpdated::SUSPENDED);

        // Paiement : réactivation et retour sur le site
        app(SubscriptionManager::class)->markPaid($subscription->invoices()->sole(), PaymentMethod::Cash, null, User::factory()->admin()->create());

        $this->assertSame(SubscriptionStatus::Active, $subscription->fresh()->statut);
        $this->assertTrue(Property::query()->onSite()->whereKey($property->id)->exists());
    }

    public function test_active_subscription_is_renewed_at_the_end_of_the_period(): void
    {
        Notification::fake();
        $owner = User::factory()->owner()->create();
        $plan = SubscriptionPlan::factory()->create(['trial_days' => 0, 'monthly_price' => 10000]);
        $subscription = app(SubscriptionManager::class)->subscribe($owner, $plan, BillingCycle::Monthly);
        app(SubscriptionManager::class)->markPaid($subscription->invoices()->sole(), PaymentMethod::Cash, null, User::factory()->admin()->create());

        $end = CarbonImmutable::parse($subscription->fresh()->current_period_end);
        app(SubscriptionManager::class)->process($end->addDay());

        $subscription->refresh();
        $this->assertSame(2, $subscription->invoices()->count());
        $this->assertSame(SubscriptionStatus::PastDue, $subscription->statut);
        $this->assertSame($end->addDay()->toDateString(), $subscription->current_period_start->toDateString());
    }

    public function test_plan_limits_block_new_properties_when_subscriptions_are_required(): void
    {
        $this->requireSubscriptions();
        $owner = User::factory()->owner()->create();

        // Sans abonnement : redirigé vers « Mon abonnement »
        $this->actingAs($owner)->get(route('admin.etablissements.create'))->assertRedirect(route('admin.abonnement.show'));

        $plan = SubscriptionPlan::factory()->create(['max_properties' => 1]);
        app(SubscriptionManager::class)->subscribe($owner, $plan, BillingCycle::Monthly);

        $this->actingAs($owner)->get(route('admin.etablissements.create'))->assertOk();

        Property::factory()->for($owner, 'owner')->create();
        $this->actingAs($owner)->get(route('admin.etablissements.create'))
            ->assertRedirect(route('admin.abonnement.show'))
            ->assertSessionHas('error');

        // Une formule plus petite que l'usage actuel est refusée
        $tiny = SubscriptionPlan::factory()->create(['max_properties' => 1, 'max_units' => 0 + 1]);
        Unit::factory()->count(2)->for($owner->properties()->first())->create();
        $this->actingAs($owner)->post(route('admin.abonnement.subscribe'), ['formule' => $tiny->id, 'cycle' => 'monthly'])->assertSessionHas('error');
    }

    public function test_nothing_changes_while_subscriptions_are_optional(): void
    {
        $owner = User::factory()->owner()->create();
        $property = Property::factory()->for($owner, 'owner')->create(['statut' => PropertyStatus::Published]);

        $this->assertFalse(SubscriptionManager::required());
        $this->assertTrue(Property::query()->onSite()->whereKey($property->id)->exists());
        $this->actingAs($owner)->get(route('admin.etablissements.create'))->assertOk();
    }

    public function test_exempt_accounts_escape_every_subscription_rule(): void
    {
        Notification::fake();
        $this->requireSubscriptions();

        // Propriétaire exempté sans abonnement : ajoute librement, reste en ligne
        $owner = User::factory()->owner()->create(['subscription_exempt' => true]);
        $property = Property::factory()->for($owner, 'owner')->create(['statut' => PropertyStatus::Published]);

        $this->actingAs($owner)->get(route('admin.etablissements.create'))->assertOk();
        $this->assertTrue(Property::query()->onSite()->whereKey($property->id)->exists());
        $this->actingAs($owner)->get(route('admin.abonnement.show'))->assertSee('Compte exempté d’abonnement');

        // Avec un essai terminé : ni facture, ni suspension
        $plan = SubscriptionPlan::factory()->create(['trial_days' => 7, 'max_properties' => 1]);
        $subscription = app(SubscriptionManager::class)->subscribe($owner, $plan, BillingCycle::Monthly);
        Property::factory()->for($owner, 'owner')->create();

        $result = app(SubscriptionManager::class)->process(CarbonImmutable::today()->addDays(60));

        $this->assertSame(0, $result['invoiced']);
        $this->assertSame(0, $subscription->invoices()->count());
        $this->assertSame(SubscriptionStatus::Trial, $subscription->fresh()->statut);
        $this->assertNull(app(SubscriptionManager::class)->propertyBlocker($owner->fresh()), 'Pas de limite de formule');

        // Un administrateur exempté crée un établissement même sans abonnement
        $this->actingAs(User::factory()->admin()->create(['subscription_exempt' => true]))
            ->get(route('admin.etablissements.create'))->assertOk();

        // Un propriétaire non exempté reste soumis aux règles
        $this->actingAs(User::factory()->owner()->create())->get(route('admin.etablissements.create'))
            ->assertRedirect(route('admin.abonnement.show'));
    }

    public function test_access_rules(): void
    {
        $owner = User::factory()->owner()->create();
        $other = User::factory()->owner()->create();
        $plan = SubscriptionPlan::factory()->create(['trial_days' => 0]);
        $subscription = app(SubscriptionManager::class)->subscribe($other, $plan, BillingCycle::Monthly);
        $invoice = $subscription->invoices()->sole();

        $this->actingAs($owner)->get(route('admin.abonnement.invoice', $invoice))->assertForbidden();
        $this->actingAs($owner)->get(route('admin.abonnements.index'))->assertForbidden();
        $this->actingAs(User::factory()->create())->get(route('admin.abonnement.show'))->assertRedirect(route('client.dashboard'));

        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)->get(route('admin.abonnements.index'))->assertOk()->assertSee($other->name);
        $this->actingAs($admin)->get(route('admin.abonnements.show', $subscription))->assertOk()->assertSee($invoice->number);
    }

    public function test_admin_subscribes_an_owner_and_extends_the_trial(): void
    {
        $admin = User::factory()->admin()->create();
        $owner = User::factory()->owner()->create();
        $plan = SubscriptionPlan::factory()->create(['trial_days' => 30]);

        $this->actingAs($admin)->post(route('admin.abonnements.store'), ['proprietaire' => $owner->id, 'formule' => $plan->id, 'cycle' => 'monthly'])
            ->assertRedirect();

        $subscription = Subscription::sole();
        $end = $subscription->trial_ends_at->copy();

        $this->actingAs($admin)->patch(route('admin.abonnements.extend', $subscription), ['jours' => 15])->assertSessionHas('success');
        $this->assertTrue($subscription->fresh()->trial_ends_at->equalTo($end->addDays(15)));
    }
}
