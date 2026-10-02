<?php

namespace Tests\Feature\Admin;

use App\Enums\PropertyStatus;
use App\Models\City;
use App\Models\Equipment;
use App\Models\Property;
use App\Models\PropertyType;
use App\Models\Unit;
use App\Models\UnitType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Logement entier (« Gestion des unités » désactivée) : un studio, un appartement ou une villa décrit dans
 * le formulaire de l'établissement, dont l'unique unité est créée et tenue à jour automatiquement.
 */
class WholeHomeTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private UnitType $studio;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        Notification::fake();
        $this->owner = User::factory()->owner()->create();
        $this->studio = UnitType::factory()->create(['name' => 'Studio']);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return [
            'type_etablissement_id' => PropertyType::factory()->create()->id,
            'nom' => 'Studio Riviera Palmeraie',
            'city_id' => City::factory()->create()->id,
            'commune' => 'Cocody',
            'adresse' => 'Riviera Palmeraie, rue I 45',
            'telephone' => '07 01 02 03 04',
            'politique_annulation' => 'flexible',
            'logement_type_id' => $this->studio->id,
            'logement_capacite' => '2',
            'logement_chambres' => '0',
            'logement_lits' => '1',
            'logement_salles_bain' => '1',
            'logement_superficie' => '32',
            'logement_prix' => '25 000 FCFA',
            'gallery' => [UploadedFile::fake()->image('studio.jpg')],
            'statut' => 'actif',
            'meta_title' => 'Studio meublé à la Riviera',
            'slug' => '',
            ...$overrides,
        ];
    }

    public function test_a_studio_is_created_with_its_single_unit_in_one_form(): void
    {
        $wifi = Equipment::factory()->create(['name' => 'Wi-Fi']);

        $this->actingAs($this->owner)->post(route('admin.etablissements.store'), $this->payload(['logement_equipements' => [$wifi->id]]))
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success');

        $property = Property::sole();
        $unit = $property->units()->sole();

        $this->assertFalse($property->manages_units);
        $this->assertSame(PropertyStatus::Pending, $property->statut);
        $this->assertSame('Studio', $unit->name);
        $this->assertSame(1, $unit->quantity);
        $this->assertSame(2, $unit->max_adults);
        $this->assertSame(0, $unit->bedrooms);
        $this->assertSame(25000, $unit->base_price);
        $this->assertSame(32, (int) $unit->size_m2);
        $this->assertSame([$wifi->id], $unit->equipments->modelKeys());

        // Une fois validé, le studio est réservable sur le site
        $property->update(['statut' => PropertyStatus::Published]);
        $this->get(route('residences.show', $property))->assertOk()->assertSee('25 000 FCFA')->assertSee('Logement entier');
    }

    public function test_the_home_must_be_described(): void
    {
        $this->actingAs($this->owner)
            ->post(route('admin.etablissements.store'), $this->payload(['logement_type_id' => '', 'logement_prix' => '', 'logement_lits' => '']))
            ->assertSessionHasErrors(['logement_type_id', 'logement_prix', 'logement_lits']);

        $this->assertDatabaseCount('properties', 0);
        $this->assertDatabaseCount('units', 0);
    }

    public function test_editing_the_form_updates_the_same_unit(): void
    {
        $this->actingAs($this->owner)->post(route('admin.etablissements.store'), $this->payload());
        $property = Property::sole();
        $unit = $property->units()->sole();

        // Le formulaire rouvre avec le logement prérempli
        $this->actingAs($this->owner)->get(route('admin.etablissements.edit', $property))->assertOk()->assertSee('value="25000"', false);

        $this->actingAs($this->owner)->put(route('admin.etablissements.update', $property), $this->payload([
            'slug' => $property->slug,
            'gallery' => [],
            'logement_capacite' => '3',
            'logement_prix' => '30000',
            'logement_prix_promo' => '27000',
        ]))->assertSessionHasNoErrors();

        $this->assertSame(1, $property->units()->count());
        $unit->refresh();
        $this->assertSame(3, $unit->max_adults);
        $this->assertSame(30000, $unit->base_price);
        $this->assertSame(27000, $unit->promo_price);
    }

    public function test_the_units_menu_sends_back_to_the_form(): void
    {
        $this->actingAs($this->owner)->post(route('admin.etablissements.store'), $this->payload());
        $property = Property::sole();
        $form = route('admin.etablissements.edit', ['etablissement' => $property, 'etape' => 'accueil']);

        // Pas de deuxième unité pour un logement entier
        $this->actingAs($this->owner)->get(route('admin.etablissements.unites.create', $property))->assertRedirect($form);
        $this->actingAs($this->owner)->post(route('admin.etablissements.unites.store', $property), [
            'type_unite_id' => $this->studio->id,
            'nom' => 'Second studio',
            'nombre_unite' => '1',
            'capacite' => '2',
            'nombre_chambre' => '0',
            'nombre_lit' => '1',
            'nombre_salle_bain' => '1',
            'prix' => '20000',
            'images' => [UploadedFile::fake()->image('second.jpg')],
            'statut' => 'actif',
        ])->assertRedirect($form);
        $this->assertSame(1, $property->units()->count());

        // Son unité se modifie dans le formulaire de l'établissement
        $this->actingAs($this->owner)->get(route('admin.unites.edit', $property->units()->sole()))->assertRedirect($form);
        $this->actingAs($this->owner)->get(route('admin.etablissements.show', $property))->assertSee('Logement')->assertDontSee(route('admin.etablissements.unites.create', $property));
    }

    public function test_a_property_with_several_units_cannot_become_a_whole_home(): void
    {
        $property = Property::factory()->for($this->owner, 'owner')->create(['manages_units' => true]);
        Unit::factory()->count(2)->for($property)->create();

        $this->actingAs($this->owner)->put(route('admin.etablissements.update', $property), $this->payload(['slug' => $property->slug]))
            ->assertSessionHasErrors('gestion_unites');

        $this->assertSame(2, $property->units()->count());
    }

    public function test_a_published_property_without_active_unit_is_hidden_from_the_site(): void
    {
        $empty = Property::factory()->for($this->owner, 'owner')->create(['name' => 'Résidence Vide']);

        $this->get(route('residences.show', $empty))->assertNotFound();
        $this->assertFalse(Property::query()->onSite()->whereKey($empty->id)->exists());

        // Son propriétaire garde l'aperçu
        $this->actingAs($this->owner)->get(route('residences.show', $empty))->assertOk()->assertSee('Aperçu');
    }
}
