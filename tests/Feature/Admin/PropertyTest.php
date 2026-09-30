<?php

namespace Tests\Feature\Admin;

use App\Enums\PropertyStatus;
use App\Models\City;
use App\Models\Property;
use App\Models\PropertyImage;
use App\Models\PropertyType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PropertyTest extends TestCase
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
            'type_etablissement_id' => PropertyType::factory()->create()->id,
            'nom' => 'Hôtel Palm Club',
            'description' => 'Un hôtel en bord de lagune.',
            'city_id' => City::factory()->create()->id,
            'commune' => 'Cocody',
            'quartier' => 'Angré',
            'adresse' => 'Rue des Jardins',
            'latitude' => '5.3600000',
            'longitude' => '-3.9900000',
            'telephone' => '07 01 02 03 04',
            'email' => 'contact@palmclub.ci',
            'site_web' => 'https://palmclub.ci',
            'check_in' => '15:00',
            'check_out' => '11:00',
            'etoile' => '4',
            'gestion_unites' => '1',
            'gallery' => [
                UploadedFile::fake()->image('facade.jpg'),
                UploadedFile::fake()->image('piscine.jpg'),
            ],
            'gallery_cover' => 'piscine.jpg',
            'logo' => UploadedFile::fake()->image('logo.png'),
            'status' => 'actif',
            'meta_title' => 'Hôtel Palm Club Abidjan',
            'meta_description' => 'Hôtel 4 étoiles à Cocody.',
            'slug' => '',
            ...$overrides,
        ];
    }

    public function test_owner_creates_a_property_with_its_logo_and_gallery(): void
    {
        $owner = User::factory()->owner()->create();

        $this->actingAs($owner)->get(route('admin.etablissements.create'))->assertOk()->assertSee('Nouvel établissement');

        $this->actingAs($owner)->post(route('admin.etablissements.store'), $this->payload())
            ->assertRedirect(route('admin.etablissements.index'))
            ->assertSessionHasNoErrors();

        $property = Property::firstWhere('name', 'Hôtel Palm Club');

        $this->assertTrue($property->owner->is($owner));
        $this->assertSame('hotel-palm-club', $property->slug);
        $this->assertSame(PropertyStatus::Published, $property->status);
        $this->assertNotNull($property->published_at);
        $this->assertSame('0701020304', $property->phone);
        $this->assertSame('Cocody', $property->district);
        $this->assertSame('Angré', $property->neighborhood);
        $this->assertSame(4, $property->star_rating);
        $this->assertTrue($property->manages_units);
        Storage::disk('public')->assertExists($property->logo_path);

        $this->assertCount(2, $property->images);
        $cover = $property->images()->where('is_cover', true)->sole();
        $this->assertStringContainsString("properties/{$property->id}/gallery", $cover->path);
        $this->assertSame(2, $cover->position);
    }

    public function test_gallery_is_required(): void
    {
        $this->actingAs(User::factory()->owner()->create())
            ->post(route('admin.etablissements.store'), $this->payload(['gallery' => []]))
            ->assertSessionHasErrors('gallery');

        $this->assertDatabaseCount('properties', 0);
    }

    public function test_inactive_status_saves_a_draft(): void
    {
        $this->actingAs(User::factory()->owner()->create())
            ->post(route('admin.etablissements.store'), $this->payload(['status' => 'inactif']));

        $this->assertSame(PropertyStatus::Draft, Property::sole()->status);
    }

    public function test_owner_only_sees_and_edits_their_own_properties(): void
    {
        $owner = User::factory()->owner()->create();
        $mine = Property::factory()->for($owner, 'owner')->create(['name' => 'Résidence Les Palmiers']);
        $other = Property::factory()->create(['name' => 'Hôtel du Concurrent']);

        $this->actingAs($owner)->get(route('admin.etablissements.index'))
            ->assertSee('Résidence Les Palmiers')
            ->assertDontSee('Hôtel du Concurrent');

        $this->actingAs($owner)->get(route('admin.etablissements.edit', $mine))->assertOk();
        $this->actingAs($owner)->get(route('admin.etablissements.edit', $other))->assertForbidden();
        $this->actingAs($owner)->delete(route('admin.etablissements.destroy', $other))->assertForbidden();
    }

    public function test_admin_sees_every_property(): void
    {
        Property::factory()->create(['name' => 'Hôtel du Concurrent']);

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.etablissements.index'))
            ->assertSee('Hôtel du Concurrent');
    }

    public function test_update_removes_images_adds_new_ones_and_changes_the_cover(): void
    {
        $owner = User::factory()->owner()->create();
        $property = Property::factory()->for($owner, 'owner')->create(['logo_path' => 'properties/1/logo/old.png']);
        Storage::disk('public')->put('properties/1/logo/old.png', 'x');
        $kept = PropertyImage::factory()->for($property)->create(['is_cover' => true, 'position' => 0]);
        $removed = PropertyImage::factory()->for($property)->create(['is_cover' => false, 'position' => 1]);

        $this->actingAs($owner)->get(route('admin.etablissements.edit', $property))->assertOk();

        $this->actingAs($owner)->put(route('admin.etablissements.update', $property), $this->payload([
            'nom' => 'Nouveau nom',
            'slug' => $property->slug,
            'logo' => null,
            'deleted_logo' => '1',
            'gallery' => [UploadedFile::fake()->image('terrasse.jpg')],
            'deleted_gallery' => json_encode([(string) $removed->id]),
            'gallery_cover' => 'terrasse.jpg',
        ]))->assertRedirect(route('admin.etablissements.index'))->assertSessionHasNoErrors();

        $property->refresh();

        $this->assertSame('Nouveau nom', $property->name);
        $this->assertNull($property->logo_path);
        Storage::disk('public')->assertMissing('properties/1/logo/old.png');
        $this->assertModelMissing($removed);
        $this->assertFalse($kept->fresh()->is_cover);
        $this->assertStringContainsString('gallery', $property->images()->where('is_cover', true)->sole()->path);
    }

    public function test_slug_must_be_unique(): void
    {
        Property::factory()->create(['slug' => 'hotel-palm-club']);

        $this->actingAs(User::factory()->owner()->create())
            ->post(route('admin.etablissements.store'), $this->payload())
            ->assertSessionHasErrors('slug');
    }

    public function test_owner_deletes_their_property(): void
    {
        $owner = User::factory()->owner()->create();
        $property = Property::factory()->for($owner, 'owner')->create();

        $this->actingAs($owner)->delete(route('admin.etablissements.destroy', $property))
            ->assertRedirect(route('admin.etablissements.index'));

        $this->assertSoftDeleted($property);
    }
}
