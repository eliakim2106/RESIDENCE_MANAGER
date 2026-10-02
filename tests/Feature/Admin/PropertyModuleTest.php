<?php

namespace Tests\Feature\Admin;

use App\Enums\CancellationPolicy;
use App\Enums\PropertyStatus;
use App\Models\City;
use App\Models\Property;
use App\Models\PropertyImage;
use App\Models\PropertyType;
use App\Models\Reservation;
use App\Models\Unit;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Module Établissements : liste (onglets, filtres), fiche, suppression sécurisée,
 * publication depuis la fiche et nouveaux champs du formulaire (conditions de séjour).
 */
class PropertyModuleTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
        $this->owner = User::factory()->owner()->create();
    }

    private function property(array $attributes = []): Property
    {
        return Property::factory()->for($this->owner, 'owner')->create($attributes);
    }

    public function test_list_tabs_filters_and_scope(): void
    {
        $abidjan = City::factory()->create(['name' => 'Abidjan']);
        $hotel = PropertyType::factory()->create(['name' => 'Hôtel']);

        $this->property(['name' => 'Publié Abidjan', 'statut' => PropertyStatus::Published, 'city_id' => $abidjan->id, 'property_type_id' => $hotel->id]);
        $this->property(['name' => 'En attente', 'statut' => PropertyStatus::Pending]);
        $this->property(['name' => 'Brouillon simple', 'statut' => PropertyStatus::Draft, 'moderation_note' => null]);
        $this->property(['name' => 'Refusé', 'statut' => PropertyStatus::Draft, 'moderation_note' => 'Photos floues, merci de les remplacer.']);
        $this->property(['name' => 'Suspendu', 'statut' => PropertyStatus::Suspended, 'moderation_note' => 'Plaintes répétées.']);
        Property::factory()->create(['name' => 'Chez un autre']);

        $response = $this->actingAs($this->owner)->get(route('admin.etablissements.index'))->assertOk();
        $response->assertSee('Publié Abidjan')->assertDontSee('Chez un autre')->assertDontSee('<th>Propriétaire</th>', false);

        $this->actingAs($this->owner)->get(route('admin.etablissements.index', ['statut' => 'refuses']))
            ->assertSee('Refusé')->assertDontSee('Brouillon simple');
        $this->actingAs($this->owner)->get(route('admin.etablissements.index', ['statut' => 'brouillons']))
            ->assertSee('Brouillon simple')->assertDontSee('Photos floues');
        $this->actingAs($this->owner)->get(route('admin.etablissements.index', ['ville' => $abidjan->slug]))
            ->assertSee('Publié Abidjan')->assertDontSee('En attente</a>', false);
        $this->actingAs($this->owner)->get(route('admin.etablissements.index', ['type' => $hotel->slug, 'tri' => 'nom']))
            ->assertSee('Publié Abidjan')->assertDontSee('Suspendu</a>', false);

        // L'administrateur voit tout, avec le propriétaire
        $this->actingAs(User::factory()->admin()->create())->get(route('admin.etablissements.index'))
            ->assertSee('Chez un autre')->assertSee('<th>Propriétaire</th>', false)->assertSee($this->owner->name);
    }

    public function test_property_page_shows_activity_and_is_restricted_to_its_owner(): void
    {
        $property = $this->property(['statut' => PropertyStatus::Published, 'house_rules' => 'Pas de bruit après 22 h.', 'allows_pets' => true]);
        Unit::factory()->for($property)->create(['name' => 'Suite Lagune']);
        Reservation::factory()->confirmed()->for($property)->create([
            'guest_name' => 'Awa Koné',
            'check_in' => CarbonImmutable::today()->addDays(3),
            'check_out' => CarbonImmutable::today()->addDays(5),
        ]);

        $this->actingAs($this->owner)->get(route('admin.etablissements.show', $property))
            ->assertOk()
            ->assertSee($property->name)
            ->assertSee('Suite Lagune')
            ->assertSee('Awa Koné')
            ->assertSee('Pas de bruit après 22 h.')
            ->assertSee('Suppression impossible');

        $this->actingAs(User::factory()->owner()->create())->get(route('admin.etablissements.show', $property))->assertForbidden();
        $this->actingAs(User::factory()->admin()->create())->get(route('admin.etablissements.show', $property))->assertOk()->assertSee('Suspendre');
    }

    public function test_property_with_upcoming_reservations_cannot_be_deleted(): void
    {
        $property = $this->property();
        $reservation = Reservation::factory()->confirmed()->for($property)->create([
            'check_in' => CarbonImmutable::today()->addDays(10),
            'check_out' => CarbonImmutable::today()->addDays(12),
        ]);

        $this->actingAs($this->owner)->delete(route('admin.etablissements.destroy', $property))
            ->assertSessionHas('error', fn (string $message) => str_contains($message, '1 réservation'));
        $this->assertNotSoftDeleted($property);

        // Séjour annulé : plus rien ne bloque
        $reservation->update(['statut' => 'cancelled']);
        $this->actingAs($this->owner)->delete(route('admin.etablissements.destroy', $property))->assertRedirect(route('admin.etablissements.index'));
        $this->assertSoftDeleted($property);
    }

    public function test_owner_submits_and_unpublishes_from_the_page(): void
    {
        $property = $this->property(['statut' => PropertyStatus::Draft]);

        // Sans unité ni photo : refusé avec une explication
        $this->actingAs($this->owner)->patch(route('admin.etablissements.submit', $property))->assertSessionHas('error');
        $this->assertSame(PropertyStatus::Draft, $property->fresh()->statut);

        Unit::factory()->for($property)->create();
        PropertyImage::factory()->for($property)->create();

        $this->actingAs($this->owner)->patch(route('admin.etablissements.submit', $property))->assertSessionHas('success');
        $this->assertSame(PropertyStatus::Pending, $property->fresh()->statut);

        // Un propriétaire ne publie pas lui-même
        $this->actingAs($this->owner)->patch(route('admin.etablissements.publish', $property))->assertForbidden();

        $this->actingAs(User::factory()->admin()->create())->patch(route('admin.etablissements.publish', $property))->assertSessionHas('success');
        $this->assertSame(PropertyStatus::Published, $property->fresh()->statut);

        $this->actingAs($this->owner)->patch(route('admin.etablissements.unpublish', $property))->assertSessionHas('success');
        $this->assertSame(PropertyStatus::Draft, $property->fresh()->statut);
    }

    public function test_a_suspended_property_cannot_be_taken_offline_by_its_owner(): void
    {
        $property = $this->property(['statut' => PropertyStatus::Suspended, 'moderation_note' => 'Suspension administrative.']);

        $this->actingAs($this->owner)->patch(route('admin.etablissements.unpublish', $property))->assertSessionHas('error');
        $this->assertSame(PropertyStatus::Suspended, $property->fresh()->statut);
    }

    public function test_form_saves_stay_conditions_and_reopens_the_step_in_error(): void
    {
        $property = $this->property(['statut' => PropertyStatus::Published]);
        PropertyImage::factory()->for($property)->create();

        $payload = [
            'type_etablissement_id' => $property->property_type_id,
            'nom' => $property->name,
            'resume' => 'Résidence calme à deux pas de la plage.',
            'city_id' => $property->city_id,
            'commune' => 'Cocody',
            'adresse' => 'Rue des Jardins',
            'telephone' => '0701020304',
            'check_in' => '14:00',
            'arrivee_jusqua' => '22:00',
            'check_out' => '12:00',
            'politique_annulation' => 'strict',
            'reglement' => 'Pièce d’identité exigée.',
            'animaux' => '1',
            'fumeurs' => '0',
            'fetes' => '0',
            'gestion_unites' => '1',
            'statut' => 'actif',
            'meta_title' => 'Titre',
            'slug' => $property->slug,
        ];

        // Arrivée 14 h, départ 12 h (le lendemain) : accepté
        $this->actingAs($this->owner)->put(route('admin.etablissements.update', $property), $payload)
            ->assertRedirect(route('admin.etablissements.show', $property))
            ->assertSessionHasNoErrors();

        $property->refresh();
        $this->assertSame(CancellationPolicy::Strict, $property->cancellation_policy);
        $this->assertSame('22:00', substr($property->check_in_until, 0, 5));
        $this->assertSame('Pièce d’identité exigée.', $property->house_rules);
        $this->assertSame('Résidence calme à deux pas de la plage.', $property->short_description);
        $this->assertTrue($property->allows_pets);
        $this->assertFalse($property->allows_smoking);

        // Heure limite avant l'heure d'arrivée : erreur, l'étape 3 se rouvre avec le résumé des erreurs
        $this->actingAs($this->owner)->from(route('admin.etablissements.edit', $property))
            ->put(route('admin.etablissements.update', $property), ['arrivee_jusqua' => '10:00'] + $payload)
            ->assertSessionHasErrors('arrivee_jusqua');

        $this->actingAs($this->owner)->from(route('admin.etablissements.edit', $property))
            ->followingRedirects()
            ->put(route('admin.etablissements.update', $property), ['arrivee_jusqua' => '10:00'] + $payload)
            ->assertSee('data-start-step="3"', false)
            ->assertSee('Un point à corriger')
            ->assertSee('Accueil &amp; conditions', false);
    }

    public function test_edit_opens_the_requested_step_and_shows_the_readiness_checklist(): void
    {
        $property = $this->property(['statut' => PropertyStatus::Draft]);

        $this->actingAs($this->owner)->get(route('admin.etablissements.edit', ['etablissement' => $property, 'etape' => 'medias']))
            ->assertOk()
            ->assertSee('data-start-step="4"', false)
            ->assertSee('data-edit-mode="1"', false)
            ->assertSee('Avant la mise en ligne')
            ->assertSee('Aucune unité pour le moment')
            ->assertSee('Coordonnées de reversement')
            ->assertSee('Politique d’annulation');

        $this->actingAs($this->owner)->get(route('admin.etablissements.create'))
            ->assertSee('data-start-step="1"', false)
            ->assertSee('data-edit-mode="0"', false);
    }
}
