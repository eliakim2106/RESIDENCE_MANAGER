<?php

namespace Tests\Feature\Admin;

use App\Enums\ActiveStatus;
use App\Models\Equipment;
use App\Models\Property;
use App\Models\PropertyType;
use App\Models\UnitType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Types d'établissement, types d'unité et équipements (réservés aux administrateurs).
 */
class CatalogTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    public function test_admin_manages_property_types(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->get(route('admin.types-etablissement.create'))->assertOk();

        $this->actingAs($admin)->post(route('admin.types-etablissement.store'), [
            'nom' => 'Résidence meublée',
            'icon' => 'fa-building',
            'description' => 'Résidence équipée.',
            'statut' => 'actif',
        ])->assertRedirect(route('admin.types-etablissement.index'));

        $type = PropertyType::firstWhere('name', 'Résidence meublée');
        $this->assertSame('residence-meublee', $type->slug);
        $this->assertSame(ActiveStatus::Active, $type->statut);

        $this->actingAs($admin)->get(route('admin.types-etablissement.index'))->assertSee('Résidence meublée');
        $this->actingAs($admin)->get(route('admin.types-etablissement.edit', $type))->assertOk();

        $this->actingAs($admin)->put(route('admin.types-etablissement.update', $type), [
            'nom' => 'Résidence meublée',
            'icon' => 'fa-building',
            'description' => 'Résidence équipée.',
            'statut' => 'inactif',
        ])->assertRedirect();

        $this->assertSame(ActiveStatus::Inactive, $type->fresh()->statut);

        $this->actingAs($admin)->delete(route('admin.types-etablissement.destroy', $type))->assertRedirect();
        $this->assertModelMissing($type);
    }

    public function test_property_type_names_are_unique(): void
    {
        PropertyType::factory()->create(['name' => 'Villa']);

        $this->actingAs($this->admin())->post(route('admin.types-etablissement.store'), [
            'nom' => 'Villa', 'icon' => 'fa-house', 'description' => 'Villa.', 'statut' => 'actif',
        ])->assertSessionHasErrors(['nom' => "Ce type d'établissement existe déjà."]);
    }

    public function test_a_type_used_by_a_property_cannot_be_deleted(): void
    {
        $property = Property::factory()->create();

        $this->actingAs($this->admin())
            ->delete(route('admin.types-etablissement.destroy', $property->propertyType))
            ->assertSessionHas('error');

        $this->assertModelExists($property->propertyType);
    }

    public function test_admin_manages_unit_types(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->get(route('admin.types-unite.create'))->assertOk();

        $this->actingAs($admin)->post(route('admin.types-unite.store'), [
            'nom' => 'Suite Junior', 'icon' => 'fa-door-open', 'description' => 'Suite élégante.', 'statut' => 'actif',
        ])->assertRedirect(route('admin.types-unite.index'));

        $this->assertDatabaseHas('unit_types', ['name' => 'Suite Junior', 'slug' => 'suite-junior']);
        $this->actingAs($admin)->get(route('admin.types-unite.index'))->assertSee('Suite Junior');
        $this->actingAs($admin)->get(route('admin.types-unite.edit', UnitType::first()))->assertOk();
    }

    public function test_admin_manages_equipments(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->get(route('admin.equipements.create'))->assertOk();

        $this->actingAs($admin)->post(route('admin.equipements.store'), [
            'nom' => 'Wi-Fi fibre', 'icon' => 'fa-wifi', 'category' => 'general', 'is_popular' => '1', 'statut' => 'actif',
        ])->assertRedirect(route('admin.equipements.index'));

        $equipment = Equipment::firstWhere('name', 'Wi-Fi fibre');
        $this->assertTrue($equipment->is_popular);
        $this->assertSame('fa-wifi', $equipment->fa_icon);

        $this->actingAs($admin)->get(route('admin.equipements.index'))->assertSee('Wi-Fi fibre');
        $this->actingAs($admin)->get(route('admin.equipements.edit', $equipment))->assertOk();

        $this->actingAs($admin)->delete(route('admin.equipements.destroy', $equipment))->assertRedirect();
        $this->assertModelMissing($equipment);
    }

    public function test_short_icon_names_from_demo_data_are_translated_to_font_awesome(): void
    {
        $this->assertSame('fa-person-swimming', Equipment::factory()->make(['icon' => 'waves'])->fa_icon);
        $this->assertSame('fa-hotel', PropertyType::factory()->make(['icon' => 'hotel'])->fa_icon);
    }
}
