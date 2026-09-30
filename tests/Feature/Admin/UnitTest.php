<?php

namespace Tests\Feature\Admin;

use App\Enums\UnitStatus;
use App\Models\Equipment;
use App\Models\Property;
use App\Models\Unit;
use App\Models\UnitImage;
use App\Models\UnitType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UnitTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return [
            'type_unite_id' => UnitType::factory()->create()->id,
            'nom' => 'Chambre double vue mer',
            'nombre_unite' => '5',
            'capacite' => '2',
            'nombre_chambre' => '1',
            'nombre_lit' => '1',
            'nombre_salle_bain' => '1',
            'surperficie' => '30',
            'prix' => '45 000 FCFA',
            'prix_promo' => '40000',
            'description' => 'Vue sur la lagune.',
            'equipement_id' => Equipment::factory()->count(2)->create()->modelKeys(),
            'images' => [UploadedFile::fake()->image('chambre.jpg'), UploadedFile::fake()->image('salle-de-bain.jpg')],
            'gallery_cover' => 'salle-de-bain.jpg',
            'status' => 'actif',
            ...$overrides,
        ];
    }

    public function test_owner_adds_a_unit_to_their_property(): void
    {
        $owner = User::factory()->owner()->create();
        $property = Property::factory()->for($owner, 'owner')->create();

        $this->actingAs($owner)->get(route('admin.etablissements.unites.create', $property))->assertOk();

        $this->actingAs($owner)->post(route('admin.etablissements.unites.store', $property), $this->payload())
            ->assertRedirect(route('admin.unites.index'))
            ->assertSessionHasNoErrors();

        $unit = $property->units()->sole();

        $this->assertSame('Chambre double vue mer', $unit->name);
        $this->assertSame(5, $unit->quantity);
        $this->assertSame(45000, $unit->base_price);
        $this->assertSame(40000, $unit->promo_price);
        $this->assertSame(30, $unit->size_m2);
        $this->assertSame(UnitStatus::Active, $unit->status);
        $this->assertCount(2, $unit->equipments);

        // L'image principale passe en première position
        $images = $unit->images()->orderBy('position')->get();
        $this->assertSame([0, 1], $images->pluck('position')->all());
        $this->assertStringContainsString("units/{$unit->id}", $images->first()->path);

        $this->actingAs($owner)->get(route('admin.unites.index'))->assertSee('Chambre double vue mer');
    }

    public function test_promo_price_must_be_lower_than_the_price(): void
    {
        $owner = User::factory()->owner()->create();
        $property = Property::factory()->for($owner, 'owner')->create();

        $this->actingAs($owner)
            ->post(route('admin.etablissements.unites.store', $property), $this->payload(['prix_promo' => '50000']))
            ->assertSessionHasErrors('prix_promo');
    }

    public function test_owner_cannot_touch_another_owners_units(): void
    {
        $owner = User::factory()->owner()->create();
        $unit = Unit::factory()->create();

        $this->actingAs($owner)->get(route('admin.etablissements.unites.create', $unit->property))->assertForbidden();
        $this->actingAs($owner)->get(route('admin.unites.edit', $unit))->assertForbidden();
        $this->actingAs($owner)->delete(route('admin.unites.destroy', $unit))->assertForbidden();
        $this->actingAs($owner)->get(route('admin.unites.index'))->assertDontSee($unit->name);
    }

    public function test_update_keeps_existing_images_and_syncs_equipments(): void
    {
        $owner = User::factory()->owner()->create();
        $unit = Unit::factory()->for(Property::factory()->for($owner, 'owner'))->create();
        $first = UnitImage::factory()->for($unit)->create(['position' => 0]);
        $second = UnitImage::factory()->for($unit)->create(['position' => 1]);

        $this->actingAs($owner)->get(route('admin.unites.edit', $unit))->assertOk();

        $this->actingAs($owner)->put(route('admin.unites.update', $unit), $this->payload([
            'images' => [],
            'equipement_id' => [],
            'gallery_cover' => (string) $second->id,
            'status' => 'inactif',
        ]))->assertRedirect(route('admin.unites.index'))->assertSessionHasNoErrors();

        $unit->refresh();

        $this->assertSame(UnitStatus::Inactive, $unit->status);
        $this->assertCount(0, $unit->equipments);
        $this->assertSame(0, $second->fresh()->position);
        $this->assertSame(1, $first->fresh()->position);
    }

    public function test_owner_deletes_their_unit(): void
    {
        $owner = User::factory()->owner()->create();
        $unit = Unit::factory()->for(Property::factory()->for($owner, 'owner'))->create();

        $this->actingAs($owner)->delete(route('admin.unites.destroy', $unit))->assertRedirect(route('admin.unites.index'));

        $this->assertSoftDeleted($unit);
    }
}
