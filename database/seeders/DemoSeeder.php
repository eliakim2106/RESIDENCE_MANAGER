<?php

namespace Database\Seeders;

use App\Enums\ActiveStatus;
use App\Enums\BillingCycle;
use App\Enums\CancellationPolicy;
use App\Enums\PayoutMethod;
use App\Models\City;
use App\Models\Equipment;
use App\Models\Payment;
use App\Models\Property;
use App\Models\PropertyImage;
use App\Models\PropertyType;
use App\Models\Reservation;
use App\Models\ReservationUnit;
use App\Models\Review;
use App\Models\SubscriptionPlan;
use App\Models\Unit;
use App\Models\UnitImage;
use App\Models\UnitRate;
use App\Models\UnitType;
use App\Models\User;
use App\Services\SubscriptionManager;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Données de démonstration : comptes, établissements, unités, réservations, paiements et avis.
 * Tous les comptes ont le mot de passe User::DEFAULT_PASSWORD (« Residence@2026 »). Ne pas exécuter en production.
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        User::factory()->superAdmin()->create([
            'name' => 'Super Administrateur',
            'email' => 'superadmin@dsholding.ci',
        ]);

        User::factory()->admin()->create([
            'name' => 'Administrateur',
            'email' => 'admin@dsholding.ci',
        ]);

        $owners = collect([
            User::factory()->owner()->create(['name' => 'Propriétaire Démo', 'email' => 'owner@dsholding.ci', 'payout_method' => PayoutMethod::MobileMoney, 'payout_account' => '07 00 00 00 00', 'payout_holder' => 'Propriétaire Démo']),
        ])->merge(User::factory()->owner()->count(3)->create());

        $clients = collect([
            User::factory()->create(['name' => 'Client Démo', 'email' => 'client@dsholding.ci']),
        ])->merge(User::factory()->count(12)->create());

        // Formules d'exemple (prix fictifs, à ajuster dans Administration > Formules) ; essai pour chaque propriétaire
        $plans = $this->demoPlans();
        $owners->each(fn (User $owner) => app(SubscriptionManager::class)->subscribe($owner, $plans['pro'], BillingCycle::Monthly));

        $cities = City::all();
        $propertyTypes = PropertyType::all();
        $unitTypes = UnitType::all();
        $equipments = Equipment::all();

        foreach ($this->demoProperties() as $index => $data) {
            $property = Property::factory()->create([
                'owner_id' => $owners[$index % $owners->count()]->id,
                'property_type_id' => $propertyTypes->firstWhere('slug', $data['type'])->id,
                'city_id' => $cities->firstWhere('slug', $data['city'])->id,
                'name' => $data['name'],
                'slug' => Str::slug($data['name']),
                'district' => $data['district'],
                'star_rating' => $data['stars'],
                'cancellation_policy' => $data['policy'],
                'is_featured' => $index < 4,
            ]);

            PropertyImage::factory()->cover()->for($property)->create();
            PropertyImage::factory()->count(5)->for($property)->create();

            $property->equipments()->attach($equipments->random(rand(8, 14))->pluck('id'));

            $units = $this->createUnits($property, $unitTypes, $equipments, $data['units']);

            $this->createReservations($property, $units, $clients);
        }
    }

    /**
     * @param  Collection<int, UnitType>  $unitTypes
     * @param  Collection<int, Equipment>  $equipments
     * @param  list<array{0: string, 1: string, 2: int, 3: int, 4: int}>  $definitions
     * @return Collection<int, Unit>
     */
    private function createUnits(Property $property, Collection $unitTypes, Collection $equipments, array $definitions): Collection
    {
        $units = new Collection;

        foreach ($definitions as [$name, $typeSlug, $price, $quantity, $adults]) {
            $unit = Unit::factory()->for($property)->create([
                'unit_type_id' => $unitTypes->firstWhere('slug', $typeSlug)->id,
                'name' => $name,
                'slug' => Str::slug($name),
                'base_price' => $price,
                'weekend_price' => (int) round($price * 1.15, -3),
                'quantity' => $quantity,
                'max_adults' => $adults,
            ]);

            UnitImage::factory()->count(3)->for($unit)->create();

            UnitRate::factory()->for($unit)->create([
                'name' => 'Fêtes de fin d’année',
                'starts_on' => now()->month(12)->day(20),
                'ends_on' => now()->addYear()->month(1)->day(3),
                'price' => (int) round($price * 1.3, -3),
                'min_nights' => 2,
            ]);

            $unit->equipments()->attach($equipments->where('category.value', '!=', 'general')->random(5)->pluck('id'));

            $units->push($unit);
        }

        return $units;
    }

    /**
     * @param  Collection<int, Unit>  $units
     * @param  \Illuminate\Support\Collection<int, User>  $clients
     */
    private function createReservations(Property $property, Collection $units, $clients): void
    {
        foreach (['completed', 'completed', 'completed', 'confirmed', 'confirmed', 'pending', 'cancelled'] as $state) {
            $client = $clients->random();
            $unit = $units->random();

            $reservation = Reservation::factory()->{$state}()->create([
                'user_id' => $client->id,
                'property_id' => $property->id,
                'cancellation_policy' => $property->cancellation_policy,
                'guest_name' => $client->name,
                'guest_email' => $client->email,
                'guest_phone' => $client->phone,
            ]);

            $subtotal = $unit->base_price * $reservation->nights;
            $serviceFee = (int) round($subtotal * 0.05);
            $total = $subtotal + $unit->cleaning_fee + $serviceFee;

            $reservation->update([
                'subtotal' => $subtotal,
                'cleaning_fee' => $unit->cleaning_fee,
                'service_fee' => $serviceFee,
                'total_amount' => $total,
                'amount_paid' => in_array($state, ['pending', 'cancelled'], true) ? 0 : $total,
            ]);

            ReservationUnit::factory()->for($reservation)->for($unit)->create([
                'price_per_night' => $unit->base_price,
                'subtotal' => $subtotal,
            ]);

            if (! in_array($state, ['pending', 'cancelled'], true)) {
                Payment::factory()->accepted()->for($reservation)->create([
                    'user_id' => $client->id,
                    'amount' => $total,
                    'paid_at' => $reservation->confirmed_at,
                ]);
            }

            if ($state === 'completed') {
                Review::factory()->for($reservation)->for($property)->create(['user_id' => $client->id]);
            }
        }

        $property->refreshRating();
    }

    /**
     * Formules d'exemple pour la démonstration : les prix sont fictifs.
     *
     * @return array<string, SubscriptionPlan>
     */
    private function demoPlans(): array
    {
        return [
            'essentiel' => SubscriptionPlan::create([
                'name' => 'Essentiel',
                'description' => 'Pour démarrer avec une villa ou quelques appartements.',
                'monthly_price' => 10000,
                'yearly_price' => 100000,
                'max_properties' => 1,
                'max_units' => 5,
                'trial_days' => 30,
                'features' => ['Réservations et paiements en ligne', 'Calendrier d’occupation'],
                'position' => 1,
                'statut' => ActiveStatus::Active,
            ]),
            'pro' => SubscriptionPlan::create([
                'name' => 'Pro',
                'description' => 'Pour les résidences et les petits hôtels.',
                'monthly_price' => 25000,
                'yearly_price' => 250000,
                'max_properties' => 3,
                'max_units' => 30,
                'trial_days' => 30,
                'features' => ['Réservations et paiements en ligne', 'Calendrier d’occupation', 'Export des réservations et paiements'],
                'is_featured' => true,
                'position' => 2,
                'statut' => ActiveStatus::Active,
            ]),
            'entreprise' => SubscriptionPlan::create([
                'name' => 'Entreprise',
                'description' => 'Pour les groupes hôteliers et les gestionnaires de plusieurs établissements.',
                'monthly_price' => 60000,
                'yearly_price' => 600000,
                'max_properties' => null,
                'max_units' => null,
                'trial_days' => 30,
                'features' => ['Réservations et paiements en ligne', 'Calendrier d’occupation', 'Export des réservations et paiements', 'Accompagnement prioritaire'],
                'position' => 3,
                'statut' => ActiveStatus::Active,
            ]),
        ];
    }

    /**
     * @return list<array{name: string, type: string, city: string, district: string, stars: ?int, policy: CancellationPolicy, units: list<array{0: string, 1: string, 2: int, 3: int, 4: int}>}>
     */
    private function demoProperties(): array
    {
        return [
            [
                'name' => 'Hôtel Lagune Prestige', 'type' => 'hotel', 'city' => 'abidjan', 'district' => 'Plateau', 'stars' => 5,
                'policy' => CancellationPolicy::Moderate,
                'units' => [
                    ['Chambre Deluxe vue lagune', 'chambre-double', 85000, 12, 2],
                    ['Suite Présidentielle', 'suite', 250000, 2, 2],
                    ['Chambre Supérieure', 'chambre-twin', 65000, 20, 2],
                ],
            ],
            [
                'name' => 'Résidence Les Cocotiers', 'type' => 'residence-meublee', 'city' => 'abidjan', 'district' => 'Cocody Riviera 3', 'stars' => null,
                'policy' => CancellationPolicy::Flexible,
                'units' => [
                    ['Studio Confort', 'studio', 25000, 6, 2],
                    ['Appartement 2 pièces standing', 'appartement-2-pieces', 40000, 4, 2],
                    ['Appartement 3 pièces famille', 'appartement-3-pieces', 60000, 2, 4],
                ],
            ],
            [
                'name' => 'Villa Océane Assinie', 'type' => 'villa', 'city' => 'assinie-mafia', 'district' => 'Assouindé', 'stars' => null,
                'policy' => CancellationPolicy::Strict,
                'units' => [
                    ['Villa 4 chambres pieds dans l’eau', 'villa-entiere', 300000, 1, 8],
                ],
            ],
            [
                'name' => 'Bassam Beach Hôtel', 'type' => 'complexe-hotelier', 'city' => 'grand-bassam', 'district' => 'Quartier France', 'stars' => 4,
                'policy' => CancellationPolicy::Moderate,
                'units' => [
                    ['Bungalow vue mer', 'chambre-double', 55000, 10, 2],
                    ['Chambre familiale', 'suite', 75000, 5, 4],
                ],
            ],
            [
                'name' => 'Résidence Marcory Zone 4', 'type' => 'residence-meublee', 'city' => 'abidjan', 'district' => 'Zone 4', 'stars' => null,
                'policy' => CancellationPolicy::Flexible,
                'units' => [
                    ['Studio Business', 'studio', 30000, 8, 2],
                    ['Appartement 2 pièces', 'appartement-2-pieces', 45000, 4, 3],
                ],
            ],
            [
                'name' => 'Hôtel Président Yamoussoukro', 'type' => 'hotel', 'city' => 'yamoussoukro', 'district' => 'Centre', 'stars' => 4,
                'policy' => CancellationPolicy::Flexible,
                'units' => [
                    ['Chambre Classique', 'chambre-simple', 35000, 15, 1],
                    ['Chambre Double Confort', 'chambre-double', 45000, 15, 2],
                ],
            ],
            [
                'name' => 'Maison d’hôtes La Baie', 'type' => 'maison-dhotes', 'city' => 'san-pedro', 'district' => 'Balmer', 'stars' => null,
                'policy' => CancellationPolicy::Flexible,
                'units' => [
                    ['Chambre Baie', 'chambre-double', 20000, 4, 2],
                ],
            ],
            [
                'name' => 'Appartement Riviera Golf', 'type' => 'appartement', 'city' => 'abidjan', 'district' => 'Riviera Golf', 'stars' => null,
                'policy' => CancellationPolicy::Moderate,
                'units' => [
                    ['Appartement 3 pièces avec piscine', 'appartement-3-pieces', 70000, 1, 5],
                ],
            ],
        ];
    }
}
