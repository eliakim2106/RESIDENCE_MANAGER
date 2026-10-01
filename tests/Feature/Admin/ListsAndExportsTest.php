<?php

namespace Tests\Feature\Admin;

use App\Enums\BillingCycle;
use App\Enums\PayoutMethod;
use App\Enums\PropertyStatus;
use App\Models\Equipment;
use App\Models\Payment;
use App\Models\Property;
use App\Models\PropertyType;
use App\Models\Reservation;
use App\Models\ReservationUnit;
use App\Models\SubscriptionPlan;
use App\Models\Unit;
use App\Models\UnitType;
use App\Models\User;
use App\Services\PayoutLedger;
use App\Services\SubscriptionManager;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\Concerns\ReadsExcelExports;
use Tests\TestCase;

/**
 * Listes refaites (unités, référentiels, utilisateurs, validations) et exports Excel de l'administration.
 */
class ListsAndExportsTest extends TestCase
{
    use ReadsExcelExports, RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
        $this->admin = User::factory()->admin()->create();
    }

    public function test_every_list_can_be_exported_to_excel(): void
    {
        $owner = User::factory()->owner()->create(['name' => 'Awa Koné']);
        $property = Property::factory()->for($owner, 'owner')->create(['name' => 'Résidence Lagune']);
        $unit = Unit::factory()->for($property)->create(['name' => 'Suite Lagune']);
        $reservation = Reservation::factory()->completed()->for($property)->create();
        Payment::factory()->accepted()->for($reservation)->create(['amount' => 50000]);
        app(SubscriptionManager::class)->subscribe($owner, SubscriptionPlan::factory()->create(['name' => 'Pro']), BillingCycle::Monthly);
        app(PayoutLedger::class)->record($owner, PayoutMethod::MobileMoney, 'OM-1', null, $this->admin);

        $expected = [
            'admin.reservations.export' => ['Référence', $reservation->reference],
            'admin.paiements.export' => ['Transaction', '50000'],
            'admin.etablissements.export' => ['Établissement', 'Résidence Lagune'],
            'admin.unites.export' => ['Unité', 'Suite Lagune'],
            'admin.types-etablissement.export' => ['Type', $property->propertyType->name],
            'admin.types-unite.export' => ['Type', $unit->unitType->name],
            'admin.equipements.export' => ['Équipement', 'Établissements'],
            'admin.utilisateurs.export' => ['Nom', 'Awa Koné'],
            'admin.abonnements.export' => ['Propriétaire', 'Pro'],
            'admin.reversements.export' => ['Numéro', 'OM-1'],
        ];

        foreach ($expected as $route => [$header, $value]) {
            $text = $this->excelText($this->actingAs($this->admin)->get(route($route)));

            $this->assertStringStartsWith($header, $text, $route);
            $this->assertStringContainsString($value, $text, $route);
        }
    }

    public function test_exports_follow_the_filters_and_the_owner_scope(): void
    {
        $owner = User::factory()->owner()->create();
        Unit::factory()->for(Property::factory()->for($owner, 'owner'))->create(['name' => 'Chambre à moi']);
        Unit::factory()->create(['name' => 'Chambre d’un autre']);

        $text = $this->excelText($this->actingAs($owner)->get(route('admin.unites.export')));
        $this->assertStringContainsString('Chambre à moi', $text);
        $this->assertStringNotContainsString('Chambre d’un autre', $text);

        // Filtre de recherche repris par l'export
        $text = $this->excelText($this->actingAs($this->admin)->get(route('admin.unites.export', ['search' => 'autre'])));
        $this->assertStringContainsString('Chambre d’un autre', $text);
        $this->assertStringNotContainsString('Chambre à moi', $text);

        // Les exports réservés aux administrateurs restent fermés aux propriétaires
        $this->actingAs($owner)->get(route('admin.utilisateurs.export'))->assertForbidden();
        $this->actingAs($owner)->get(route('admin.equipements.export'))->assertForbidden();
    }

    public function test_unit_list_filters_and_safe_deletion(): void
    {
        $hotel = Property::factory()->create(['name' => 'Hôtel Plateau']);
        $villa = Property::factory()->create(['name' => 'Villa Assinie']);
        $suite = UnitType::factory()->create(['name' => 'Suite']);
        $booked = Unit::factory()->for($hotel)->create(['name' => 'Suite présidentielle', 'unit_type_id' => $suite->id]);
        $free = Unit::factory()->for($villa)->create(['name' => 'Villa entière']);

        $this->actingAs($this->admin)->get(route('admin.unites.index', ['etablissement' => $hotel->slug]))
            ->assertOk()->assertSee('Suite présidentielle')->assertDontSee('Villa entière</strong>', false);
        $this->actingAs($this->admin)->get(route('admin.unites.index', ['type' => $suite->slug, 'tri' => 'prix-croissant']))
            ->assertOk()->assertSee('Suite présidentielle');

        // Une unité réservée pour un séjour à venir ne se supprime pas
        $reservation = Reservation::factory()->confirmed()->for($hotel)->create([
            'check_in' => CarbonImmutable::today()->addDays(5),
            'check_out' => CarbonImmutable::today()->addDays(7),
        ]);
        ReservationUnit::factory()->for($reservation)->for($booked)->create();

        $this->actingAs($this->admin)->delete(route('admin.unites.destroy', $booked))->assertSessionHas('error');
        $this->assertNotSoftDeleted($booked);

        $this->actingAs($this->admin)->delete(route('admin.unites.destroy', $free))->assertSessionHas('success');
        $this->assertSoftDeleted($free);
    }

    public function test_reference_lists_show_usage_and_protect_used_items(): void
    {
        $used = PropertyType::factory()->create(['name' => 'Hôtel']);
        PropertyType::factory()->create(['name' => 'Écolodge']);
        Property::factory()->count(2)->create(['property_type_id' => $used->id]);

        $this->actingAs($this->admin)->get(route('admin.types-etablissement.index', ['tri' => 'utilisation']))
            ->assertOk()
            ->assertSee('2 établissements')
            ->assertSee('Inutilisé')
            ->assertSee('Le plus utilisé');

        $equipment = Equipment::factory()->create(['name' => 'Piscine']);
        $equipment->properties()->attach(Property::factory()->create());

        $this->actingAs($this->admin)->get(route('admin.equipements.index', ['categorie' => $equipment->category->value]))->assertOk()->assertSee('Piscine');
        $this->actingAs($this->admin)->delete(route('admin.equipements.destroy', $equipment))->assertSessionHas('error');
        $this->assertModelExists($equipment);
    }

    public function test_users_list_summary_and_state_filter(): void
    {
        User::factory()->create(['name' => 'Compte confirmé']);
        User::factory()->unverified()->create(['name' => 'Compte à confirmer']);

        $this->actingAs($this->admin)->get(route('admin.utilisateurs.index', ['etat' => 'non-confirmes']))
            ->assertOk()
            ->assertSee('Compte à confirmer')
            ->assertDontSee('Compte confirmé</strong>', false)
            ->assertSee('à surveiller');
    }

    public function test_validations_have_a_rejected_tab_and_recent_decisions(): void
    {
        Property::factory()->create(['name' => 'Résidence refusée', 'statut' => PropertyStatus::Draft, 'moderation_note' => 'Photos floues.', 'moderated_at' => now()]);
        Property::factory()->create(['name' => 'Brouillon simple', 'statut' => PropertyStatus::Draft]);

        $this->actingAs($this->admin)->get(route('admin.validations.index', ['statut' => 'refuses']))
            ->assertOk()->assertSee('Résidence refusée')->assertDontSee('Brouillon simple')->assertSee('En attente des corrections du propriétaire');

        $this->actingAs($this->admin)->get(route('admin.validations.index'))
            ->assertOk()->assertSee('Tout est à jour')->assertSee('Dernières décisions')->assertSee('Résidence refusée');
    }
}
