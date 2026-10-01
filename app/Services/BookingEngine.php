<?php

namespace App\Services;

use App\Enums\ActiveStatus;
use App\Enums\MaintenanceStatus;
use App\Enums\PaymentState;
use App\Enums\ReservationStatus;
use App\Exceptions\WorkflowException;
use App\Models\Availability;
use App\Models\Maintenance;
use App\Models\Property;
use App\Models\Reservation;
use App\Models\ReservationUnit;
use App\Models\Unit;
use App\Models\UnitRate;
use App\Models\User;
use App\Support\SiteSettings;
use Carbon\CarbonImmutable;
use Carbon\CarbonPeriod;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Réservation en ligne depuis la fiche d'un établissement : disponibilités, prix d'un séjour, création de la demande.
 *
 * Prix d'une nuit, du plus précis au plus général : prix fixé pour ce jour (calendrier), tarif saisonnier,
 * prix du week-end (nuits du vendredi et du samedi), prix promotionnel, prix de base.
 * La demande est créée « en attente » : l'établissement la valide, le client peut la régler en ligne aussitôt.
 */
class BookingEngine
{
    /**
     * Devis d'une unité sur la période [arrivée, départ[.
     *
     * @return array{unit: Unit, available: int, nights: int, nightly: list<array{date: CarbonImmutable, price: int}>, subtotal: int, cleaning: int, average: int, issues: list<string>}
     */
    public function quote(Unit $unit, CarbonImmutable $arrival, CarbonImmutable $departure): array
    {
        $nights = (int) $arrival->diffInDays($departure);
        $dates = $nights > 0 ? array_map(fn ($day) => CarbonImmutable::parse($day), iterator_to_array(CarbonPeriod::create($arrival, $departure->subDay()))) : [];

        $calendar = Availability::query()->where('unit_id', $unit->id)->whereBetween('date', [$arrival->toDateString(), $departure->subDay()->toDateString()])->get()->keyBy(fn (Availability $day) => $day->date->toDateString());
        $rates = UnitRate::query()->where('unit_id', $unit->id)->where('starts_on', '<=', $departure->toDateString())->where('ends_on', '>=', $arrival->toDateString())->get();

        $nightly = array_map(fn (CarbonImmutable $date): array => ['date' => $date, 'price' => $this->nightPrice($unit, $date, $calendar->get($date->toDateString()), $rates)], $dates);
        $subtotal = array_sum(array_column($nightly, 'price'));

        $issues = [];
        $minNights = max([(int) $unit->min_nights, ...$calendar->pluck('min_nights')->filter()->all(), ...$rates->pluck('min_nights')->filter()->all(), 1]);

        if ($nights < $minNights) {
            $issues[] = "Séjour de {$minNights} nuit".($minNights > 1 ? 's' : '').' minimum.';
        }

        if ($unit->max_nights && $nights > $unit->max_nights) {
            $issues[] = "Séjour de {$unit->max_nights} nuits maximum.";
        }

        if ($calendar->contains(fn (Availability $day) => $day->is_closed)) {
            $issues[] = 'Fermé sur une partie de ces dates.';
        }

        $available = $calendar->contains(fn (Availability $day) => $day->is_closed) ? 0 : $this->availableQuantity($unit, $arrival, $departure);

        if ($available === 0 && $issues === []) {
            $issues[] = 'Complet à ces dates.';
        }

        return [
            'unit' => $unit,
            'available' => $available,
            'nights' => $nights,
            'nightly' => $nightly,
            'subtotal' => $subtotal,
            'cleaning' => (int) $unit->cleaning_fee,
            'average' => $nights > 0 ? (int) round($subtotal / $nights) : (int) ($unit->promo_price ?: $unit->base_price),
            'issues' => $issues,
        ];
    }

    /**
     * Devis de toutes les unités actives de l'établissement pour la période et le nombre de voyageurs.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function quotes(Property $property, CarbonImmutable $arrival, CarbonImmutable $departure, int $guests = 1): Collection
    {
        return $property->units()
            ->where('statut', ActiveStatus::Active)
            ->with(['unitType', 'images', 'equipments'])
            ->orderByRaw('COALESCE(promo_price, base_price)')
            ->get()
            ->map(function (Unit $unit) use ($arrival, $departure, $guests): array {
                $quote = $this->quote($unit, $arrival, $departure);

                // Simple indication : plusieurs logements peuvent accueillir le groupe ensemble
                $capacity = $unit->max_adults + $unit->max_children;
                $quote['notice'] = $capacity < $guests ? "{$capacity} voyageurs par logement : prenez-en plusieurs pour votre groupe." : null;

                return $quote;
            });
    }

    /**
     * Totaux d'un panier (unités et quantités) : sous-total, ménage, frais de service, total.
     *
     * @param  list<array{quote: array<string, mixed>, quantity: int}>  $lines
     * @return array{subtotal: int, cleaning: int, service: int, total: int, nights: int}
     */
    public function totals(array $lines): array
    {
        $subtotal = (int) collect($lines)->sum(fn (array $line) => $line['quote']['subtotal'] * $line['quantity']);
        $cleaning = (int) collect($lines)->sum(fn (array $line) => $line['quote']['cleaning'] * $line['quantity']);
        $service = (int) round($subtotal * self::serviceFeeRate() / 100);

        return [
            'subtotal' => $subtotal,
            'cleaning' => $cleaning,
            'service' => $service,
            'total' => $subtotal + $cleaning + $service,
            'nights' => (int) ($lines[0]['quote']['nights'] ?? 0),
        ];
    }

    public static function serviceFeeRate(): float
    {
        return max(0, (float) SiteSettings::current()->booking('service_fee_rate'));
    }

    /**
     * Crée la demande de réservation après une dernière vérification des disponibilités (unités verrouillées).
     *
     * @param  array<int, int>  $selection  identifiant d'unité => nombre de logements
     * @param  array{adults: int, children: int, name: string, email: string, phone: ?string, dial: ?string, arrival_time: ?string, requests: ?string}  $guest
     */
    public function book(User $client, Property $property, array $selection, CarbonImmutable $arrival, CarbonImmutable $departure, array $guest): Reservation
    {
        $this->assertDates($arrival, $departure);
        $selection = array_filter(array_map('intval', $selection), fn (int $quantity) => $quantity > 0);

        if ($selection === []) {
            throw new WorkflowException('Choisissez au moins un logement.');
        }

        $reservation = DB::transaction(function () use ($client, $property, $selection, $arrival, $departure, $guest): Reservation {
            // Verrou sur les unités : deux clients ne peuvent pas prendre le dernier logement en même temps
            $units = $property->units()->where('statut', ActiveStatus::Active)->whereKey(array_keys($selection))->lockForUpdate()->get()->keyBy('id');

            if ($units->count() !== count($selection)) {
                throw new WorkflowException('Un des logements choisis n’est plus proposé. Actualisez la page.');
            }

            $lines = [];
            $capacity = 0;

            foreach ($selection as $unitId => $quantity) {
                $unit = $units[$unitId];
                $quote = $this->quote($unit, $arrival, $departure);

                if ($quote['issues'] !== [] && $quote['available'] > 0) {
                    throw new WorkflowException($unit->name.' : '.$quote['issues'][0]);
                }

                if ($quote['available'] < $quantity) {
                    throw new WorkflowException($quote['available'] === 0
                        ? "« {$unit->name} » n’est plus disponible à ces dates."
                        : "Il ne reste que {$quote['available']} « {$unit->name} » à ces dates.");
                }

                $lines[] = ['quote' => $quote, 'quantity' => $quantity];
                $capacity += ($unit->max_adults + $unit->max_children) * $quantity;
            }

            $guests = $guest['adults'] + $guest['children'];

            if ($guests > $capacity) {
                throw new WorkflowException("Les logements choisis accueillent {$capacity} voyageurs au maximum, pour {$guests} indiqués.");
            }

            $totals = $this->totals($lines);

            $reservation = Reservation::create([
                'user_id' => $client->id,
                'property_id' => $property->id,
                'check_in' => $arrival,
                'check_out' => $departure,
                'nights' => $totals['nights'],
                'adults' => $guest['adults'],
                'children' => $guest['children'],
                'subtotal' => $totals['subtotal'],
                'cleaning_fee' => $totals['cleaning'],
                'service_fee' => $totals['service'],
                'tax_amount' => 0,
                'discount_amount' => 0,
                'total_amount' => $totals['total'],
                'amount_paid' => 0,
                'currency' => 'XOF',
                'statut' => ReservationStatus::Pending,
                'payment_state' => PaymentState::Unpaid,
                'cancellation_policy' => $property->cancellation_policy,
                'guest_name' => $guest['name'],
                'guest_email' => $guest['email'],
                'guest_phone' => $guest['phone'],
                'indicatif_telephone' => $guest['dial'] ?: '+225',
                'estimated_arrival_time' => $guest['arrival_time'],
                'special_requests' => $guest['requests'],
                'expires_at' => now()->addHours((int) SiteSettings::current()->booking('request_ttl_hours')),
            ]);

            foreach ($lines as $line) {
                ReservationUnit::create([
                    'reservation_id' => $reservation->id,
                    'unit_id' => $line['quote']['unit']->id,
                    'quantity' => $line['quantity'],
                    'price_per_night' => $line['quote']['average'],
                    'subtotal' => $line['quote']['subtotal'] * $line['quantity'],
                    'nightly_prices' => array_map(fn (array $night): array => ['date' => $night['date']->toDateString(), 'price' => $night['price']], $line['quote']['nightly']),
                ]);
            }

            return $reservation;
        });

        app(ReservationWorkflow::class)->announce($reservation);

        return $reservation;
    }

    /**
     * Arrivée à partir d'aujourd'hui, au moins une nuit, séjour et anticipation raisonnables.
     */
    public function assertDates(CarbonImmutable $arrival, CarbonImmutable $departure): void
    {
        $nights = (int) $arrival->diffInDays($departure, false);
        $maxNights = (int) SiteSettings::current()->booking('max_nights');
        $maxDaysAhead = (int) SiteSettings::current()->booking('max_days_ahead');

        match (true) {
            $arrival->lt(CarbonImmutable::today()) => throw new WorkflowException('La date d’arrivée est déjà passée.'),
            $nights < 1 => throw new WorkflowException('La date de départ doit suivre la date d’arrivée.'),
            $nights > $maxNights => throw new WorkflowException("Séjour de {$maxNights} nuits maximum en ligne : contactez l’établissement pour un long séjour."),
            $arrival->gt(CarbonImmutable::today()->addDays($maxDaysAhead)) => throw new WorkflowException("Les réservations sont ouvertes jusqu’à {$maxDaysAhead} jours à l’avance."),
            default => null,
        };
    }

    /*
    |--------------------------------------------------------------------------
    | CALCULS
    |--------------------------------------------------------------------------
    */

    /**
     * @param  Collection<int, UnitRate>  $rates
     */
    private function nightPrice(Unit $unit, CarbonImmutable $date, ?Availability $day, Collection $rates): int
    {
        if ($day?->price) {
            return (int) $day->price;
        }

        $rate = $rates->first(fn (UnitRate $rate) => $date->betweenIncluded($rate->starts_on, $rate->ends_on));

        if ($rate) {
            return (int) $rate->price;
        }

        // Nuits du vendredi et du samedi
        if ($unit->weekend_price && in_array($date->dayOfWeekIso, [5, 6], true)) {
            return (int) $unit->weekend_price;
        }

        return (int) ($unit->promo_price && $unit->promo_price < $unit->base_price ? $unit->promo_price : $unit->base_price);
    }

    /**
     * Logements encore libres sur toute la période : quantité − réservations qui chevauchent − plus fort blocage − maintenances.
     */
    private function availableQuantity(Unit $unit, CarbonImmutable $arrival, CarbonImmutable $departure): int
    {
        $lastNight = $departure->subDay()->toDateString();

        $booked = (int) ReservationUnit::query()
            ->where('unit_id', $unit->id)
            ->whereHas('reservation', fn ($query) => $query->overlapping(Carbon::instance($arrival), Carbon::instance($departure))->blocking())
            ->sum('quantity');

        $blocked = (int) Availability::query()->where('unit_id', $unit->id)->whereBetween('date', [$arrival->toDateString(), $lastNight])->max('blocked_quantity');

        $maintenance = (int) Maintenance::query()
            ->where('unit_id', $unit->id)
            ->where('statut', '!=', MaintenanceStatus::Done)
            ->where('starts_on', '<=', $lastNight)
            ->where('ends_on', '>=', $arrival->toDateString())
            ->sum('quantity');

        return max(0, (int) $unit->quantity - $booked - $blocked - $maintenance);
    }
}
