<?php

namespace Database\Seeders;

use App\Enums\EquipmentCategory;
use App\Models\City;
use App\Models\Equipment;
use App\Models\PropertyType;
use App\Models\Setting;
use App\Models\UnitType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Données de référence indispensables au fonctionnement (idempotent).
 */
class ReferenceDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedCities();
        $this->seedPropertyTypes();
        $this->seedUnitTypes();
        $this->seedEquipments();
        $this->seedSettings();
    }

    private function seedCities(): void
    {
        $cities = [
            ['Abidjan', 'Abidjan'],
            ['Grand-Bassam', 'Sud-Comoé'],
            ['Assinie-Mafia', 'Sud-Comoé'],
            ['Yamoussoukro', 'Yamoussoukro'],
            ['San-Pédro', 'San-Pédro'],
            ['Bouaké', 'Gbêkê'],
            ['Jacqueville', 'Grands-Ponts'],
            ['Korhogo', 'Poro'],
            ['Man', 'Tonkpi'],
            ['Sassandra', 'Gbôklé'],
        ];

        foreach ($cities as [$name, $region]) {
            City::updateOrCreate(['slug' => Str::slug($name)], ['name' => $name, 'region' => $region, 'country' => 'CI']);
        }
    }

    private function seedPropertyTypes(): void
    {
        $types = [
            ['Hôtel', 'hotel', 'Établissement hôtelier proposant des chambres et des services.'],
            ['Résidence meublée', 'building-2', 'Appartements meublés avec services, pour courts et moyens séjours.'],
            ['Appartement', 'building', 'Logement entier indépendant.'],
            ['Villa', 'house', 'Maison individuelle avec jardin ou piscine.'],
            ["Maison d'hôtes", 'home', 'Chambres chez l’habitant avec petit-déjeuner.'],
            ['Complexe hôtelier', 'palmtree', 'Hôtel avec activités et loisirs sur place (resort).'],
        ];

        foreach ($types as [$name, $icon, $description]) {
            PropertyType::updateOrCreate(['slug' => Str::slug($name)], compact('name', 'icon', 'description'));
        }
    }

    private function seedUnitTypes(): void
    {
        $types = [
            ['Chambre simple', 'bed-single'],
            ['Chambre double', 'bed-double'],
            ['Chambre twin', 'bed'],
            ['Suite', 'crown'],
            ['Studio', 'door-open'],
            ['Appartement 2 pièces', 'building'],
            ['Appartement 3 pièces', 'building'],
            ['Villa entière', 'house'],
        ];

        foreach ($types as [$name, $icon]) {
            UnitType::updateOrCreate(['slug' => Str::slug($name)], compact('name', 'icon'));
        }
    }

    private function seedEquipments(): void
    {
        $equipments = [
            ['Wi-Fi gratuit', 'wifi', EquipmentCategory::General, true],
            ['Parking gratuit', 'car', EquipmentCategory::General, true],
            ['Piscine', 'waves', EquipmentCategory::Outdoor, true],
            ['Climatisation', 'snowflake', EquipmentCategory::Room, true],
            ['Groupe électrogène', 'zap', EquipmentCategory::General, true],
            ['Sécurité 24h/24', 'shield-check', EquipmentCategory::Service, true],
            ['Restaurant', 'utensils', EquipmentCategory::Service, true],
            ['Petit-déjeuner inclus', 'coffee', EquipmentCategory::Service, true],
            ['Salle de sport', 'dumbbell', EquipmentCategory::General, false],
            ['Navette aéroport', 'plane', EquipmentCategory::Service, false],
            ['Réception 24h/24', 'concierge-bell', EquipmentCategory::Service, false],
            ['Ménage quotidien', 'sparkles', EquipmentCategory::Service, false],
            ['Blanchisserie', 'shirt', EquipmentCategory::Service, false],
            ['Accès plage', 'umbrella', EquipmentCategory::Outdoor, false],
            ['Jardin', 'trees', EquipmentCategory::Outdoor, false],
            ['Terrasse', 'sun', EquipmentCategory::Outdoor, false],
            ['Télévision écran plat', 'tv', EquipmentCategory::Room, false],
            ['Canal+', 'tv', EquipmentCategory::Room, false],
            ['Coffre-fort', 'lock', EquipmentCategory::Room, false],
            ['Balcon', 'layout-panel-top', EquipmentCategory::Room, false],
            ['Bureau', 'briefcase', EquipmentCategory::Room, false],
            ['Cuisine équipée', 'chef-hat', EquipmentCategory::Kitchen, true],
            ['Réfrigérateur', 'refrigerator', EquipmentCategory::Kitchen, false],
            ['Micro-ondes', 'microwave', EquipmentCategory::Kitchen, false],
            ['Machine à café', 'coffee', EquipmentCategory::Kitchen, false],
            ['Lave-linge', 'washing-machine', EquipmentCategory::Kitchen, false],
            ['Douche à l’italienne', 'shower-head', EquipmentCategory::Bathroom, false],
            ['Baignoire', 'bath', EquipmentCategory::Bathroom, false],
            ['Eau chaude', 'thermometer', EquipmentCategory::Bathroom, false],
            ['Articles de toilette gratuits', 'package', EquipmentCategory::Bathroom, false],
        ];

        foreach ($equipments as [$name, $icon, $category, $isPopular]) {
            Equipment::updateOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name, 'icon' => $icon, 'category' => $category, 'is_popular' => $isPopular]
            );
        }
    }

    private function seedSettings(): void
    {
        $settings = [
            'site_name' => ['DS Holding', 'general'],
            'tagline' => ['Excellence et Innovation', 'general'],
            'contact_email' => ['contact@dsholding.ci', 'general'],
            'contact_phone' => ['+225 27 22 00 00 00', 'general'],
            'address' => ['Cocody Riviera, Abidjan, Côte d’Ivoire', 'general'],
            'service_fee_percent' => ['5', 'booking'],
            'tax_percent' => ['0', 'booking'],
            'pending_reservation_minutes' => ['30', 'booking'],
            'commission_percent' => ['10', 'booking'],
            'currency' => ['XOF', 'booking'],
        ];

        foreach ($settings as $key => [$value, $group]) {
            Setting::firstOrCreate(['key' => $key], ['value' => $value, 'group' => $group]);
        }
    }
}
