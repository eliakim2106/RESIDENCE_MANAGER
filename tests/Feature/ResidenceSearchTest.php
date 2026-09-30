<?php

namespace Tests\Feature;

use App\Enums\PropertyStatus;
use App\Enums\UnitStatus;
use App\Models\City;
use App\Models\Equipment;
use App\Models\Property;
use App\Models\PropertyType;
use App\Models\Reservation;
use App\Models\ReservationUnit;
use App\Models\Unit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class ResidenceSearchTest extends TestCase
{
    use RefreshDatabase;

    private PropertyType $type;

    protected function setUp(): void
    {
        parent::setUp();

        $this->type = PropertyType::factory()->create(['name' => 'Résidence meublée', 'slug' => 'residence-meublee']);
    }

    public function test_only_published_properties_with_an_active_unit_are_listed(): void
    {
        $this->residence(['name' => 'Visible']);
        $this->residence(['name' => 'Brouillon', 'status' => PropertyStatus::Draft]);
        $this->residence(['name' => 'Sans unité active'], ['status' => UnitStatus::Inactive]);

        $this->search()
            ->assertOk()
            ->assertSee('Visible')
            ->assertDontSee('Brouillon')
            ->assertDontSee('Sans unité active');
    }

    public function test_filters_by_city_and_type(): void
    {
        $abidjan = City::factory()->create(['name' => 'Abidjan', 'slug' => 'abidjan']);
        $villa = PropertyType::factory()->create(['name' => 'Villa', 'slug' => 'villa']);

        $this->residence(['name' => 'Résidence Cocody', 'city_id' => $abidjan->id]);
        $this->residence(['name' => 'Villa Abidjan', 'city_id' => $abidjan->id, 'property_type_id' => $villa->id]);
        $this->residence(['name' => 'Villa Assinie', 'property_type_id' => $villa->id]);

        $this->search(['villes' => ['abidjan'], 'types' => ['villa']])
            ->assertSee('Villa Abidjan')
            ->assertDontSee('Résidence Cocody')
            ->assertDontSee('Villa Assinie');
    }

    public function test_filters_by_capacity_and_budget_using_the_promotional_price(): void
    {
        $this->residence(['name' => 'Studio'], ['max_adults' => 2, 'max_children' => 0, 'base_price' => 20000]);
        $this->residence(['name' => 'Grande villa'], ['max_adults' => 6, 'max_children' => 2, 'base_price' => 150000]);
        $this->residence(['name' => 'Appartement en promo'], ['max_adults' => 4, 'max_children' => 0, 'base_price' => 60000, 'promo_price' => 35000]);

        $this->search(['voyageurs' => 4, 'prix_max' => 40000])
            ->assertSee('Appartement en promo')
            ->assertSee('35 000 FCFA')
            ->assertDontSee('Studio')
            ->assertDontSee('Grande villa');
    }

    public function test_equipment_can_come_from_the_property_or_one_of_its_units(): void
    {
        $pool = Equipment::factory()->create(['name' => 'Piscine', 'slug' => 'piscine', 'is_popular' => true]);

        $this->residence(['name' => 'Piscine commune'])->equipments()->attach($pool);
        $this->residence(['name' => 'Piscine privée'])->units->first()->equipments()->attach($pool);
        $this->residence(['name' => 'Sans piscine']);

        $this->search(['equipements' => ['piscine']])
            ->assertSee('Piscine commune')
            ->assertSee('Piscine privée')
            ->assertDontSee('Sans piscine');
    }

    public function test_a_fully_booked_unit_is_not_available_on_overlapping_dates(): void
    {
        $booked = $this->residence(['name' => 'Complet'], ['quantity' => 1]);
        $this->residence(['name' => 'Libre'], ['quantity' => 1]);

        $this->reserve($booked, now()->addDays(10), now()->addDays(13));

        $overlapping = ['arrivee' => now()->addDays(12)->toDateString(), 'depart' => now()->addDays(15)->toDateString()];
        $later = ['arrivee' => now()->addDays(13)->toDateString(), 'depart' => now()->addDays(15)->toDateString()];

        $this->search($overlapping)->assertSee('Libre')->assertDontSee('Complet');

        // Le jour du départ est libre pour une nouvelle arrivée
        $this->search($later)->assertSee('Libre')->assertSee('Complet');
    }

    public function test_cancelled_reservations_do_not_block_availability(): void
    {
        $property = $this->residence(['name' => 'Annulée'], ['quantity' => 1]);

        $this->reserve($property, now()->addDays(10), now()->addDays(13), cancelled: true);

        $this->search(['arrivee' => now()->addDays(10)->toDateString(), 'depart' => now()->addDays(12)->toDateString()])
            ->assertSee('Annulée')
            ->assertSee('pour 2 nuits');
    }

    public function test_sorts_by_price(): void
    {
        $this->residence(['name' => 'Chère'], ['base_price' => 90000]);
        $this->residence(['name' => 'Abordable'], ['base_price' => 15000]);

        $this->search(['tri' => 'prix-croissant'])->assertSeeInOrder(['Abordable', 'Chère']);
        $this->search(['tri' => 'prix-decroissant'])->assertSeeInOrder(['Chère', 'Abordable']);
    }

    public function test_invalid_parameters_are_ignored(): void
    {
        $this->residence(['name' => 'Toujours visible']);

        $this->search([
            'arrivee' => 'pas-une-date',
            'depart' => '2020-01-01',
            'voyageurs' => 'beaucoup',
            'prix_min' => '-5',
            'villes' => ['<script>'],
            'note' => '42',
            'tri' => 'inconnu',
        ])->assertOk()->assertSee('Toujours visible');
    }

    public function test_shows_an_empty_state_when_nothing_matches(): void
    {
        $this->residence(['name' => 'Petite résidence'], ['max_adults' => 2, 'max_children' => 0]);

        $this->search(['voyageurs' => 12])
            ->assertOk()
            ->assertSee('Aucune résidence ne correspond à votre recherche');
    }

    /**
     * Établissement publié avec une unité active.
     *
     * @param  array<string, mixed>  $property
     * @param  array<string, mixed>  $unit
     */
    private function residence(array $property = [], array $unit = []): Property
    {
        $residence = Property::factory()->create([
            'property_type_id' => $this->type->id,
            'status' => PropertyStatus::Published,
            ...$property,
        ]);

        Unit::factory()->for($residence)->create([
            'status' => UnitStatus::Active,
            'promo_price' => null,
            ...$unit,
        ]);

        return $residence->load('units');
    }

    private function reserve(Property $property, $checkIn, $checkOut, bool $cancelled = false): void
    {
        $factory = Reservation::factory()->for($property);

        $reservation = ($cancelled ? $factory->cancelled() : $factory->confirmed())->create([
            'check_in' => $checkIn->toDateString(),
            'check_out' => $checkOut->toDateString(),
            'nights' => (int) $checkIn->diffInDays($checkOut),
        ]);

        ReservationUnit::factory()->for($reservation)->create([
            'unit_id' => $property->units->first()->id,
            'quantity' => 1,
        ]);
    }

    /**
     * @param  array<string, mixed>  $query
     */
    private function search(array $query = []): TestResponse
    {
        return $this->get(route('residences.index', $query));
    }
}
