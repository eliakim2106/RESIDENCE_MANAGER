<?php

namespace App\Services;

use App\Enums\ActiveStatus;
use App\Enums\MaintenanceStatus;
use App\Models\Availability;
use App\Models\Maintenance;
use App\Models\Property;
use App\Models\Reservation;
use App\Models\Unit;
use Carbon\CarbonImmutable;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;

/**
 * Occupation d'un établissement sur un mois : pour chaque unité et chaque nuit,
 * nombre d'exemplaires réservés, bloqués (maintenance, fermeture) et capacité.
 */
class OccupancyCalendar
{
    public readonly CarbonImmutable $start;

    public readonly CarbonImmutable $end;

    /**
     * @var list<CarbonImmutable>
     */
    public readonly array $days;

    public function __construct(public readonly Property $property, CarbonImmutable $month)
    {
        $this->start = $month->startOfMonth();
        $this->end = $month->endOfMonth()->startOfDay();
        $this->days = array_map(
            fn ($day) => CarbonImmutable::parse($day),
            iterator_to_array(CarbonPeriod::create($this->start, $this->end)),
        );
    }

    /**
     * Lignes du calendrier : une par unité active.
     *
     * @return Collection<int, array{unit: Unit, cells: array<string, array{booked: int, blocked: int, capacity: int, state: string, reservations: list<Reservation>, starts: list<Reservation>}>}>
     */
    public function rows(): Collection
    {
        $units = $this->property->units()
            ->where('statut', ActiveStatus::Active)
            ->with('unitType')
            ->orderBy('name')
            ->get();

        $unitIds = $units->pluck('id');
        $monthEnd = $this->end->addDay()->toDateString();

        // Séjours qui occupent au moins une nuit du mois
        $reservations = Reservation::query()
            ->whereBelongsTo($this->property)
            ->blocking()
            ->where('check_in', '<', $monthEnd)
            ->where('check_out', '>', $this->start->toDateString())
            ->with(['items' => fn ($query) => $query->whereIn('unit_id', $unitIds)])
            ->get();

        $maintenances = Maintenance::query()
            ->whereIn('unit_id', $unitIds)
            ->where('statut', '!=', MaintenanceStatus::Done)
            ->where('starts_on', '<=', $this->end->toDateString())
            ->where('ends_on', '>=', $this->start->toDateString())
            ->get()
            ->groupBy('unit_id');

        $closures = Availability::query()
            ->whereIn('unit_id', $unitIds)
            ->whereBetween('date', [$this->start->toDateString(), $this->end->toDateString()])
            ->where(fn ($query) => $query->where('is_closed', true)->orWhere('blocked_quantity', '>', 0))
            ->get()
            ->groupBy('unit_id');

        return $units->map(function (Unit $unit) use ($reservations, $maintenances, $closures): array {
            $capacity = max(1, (int) $unit->quantity);
            $unitReservations = $reservations->filter(fn (Reservation $reservation) => $reservation->items->contains('unit_id', $unit->id));
            $unitClosures = ($closures[$unit->id] ?? collect())->keyBy(fn (Availability $availability) => $availability->date->toDateString());
            $cells = [];

            foreach ($this->days as $day) {
                $date = $day->toDateString();

                // Nuit occupée : arrivée ce jour ou avant, départ après ce jour
                $present = $unitReservations->filter(fn (Reservation $reservation) => $reservation->check_in->toDateString() <= $date && $reservation->check_out->toDateString() > $date)->values();
                $booked = (int) $present->sum(fn (Reservation $reservation) => $reservation->items->where('unit_id', $unit->id)->sum('quantity'));

                $inMaintenance = (int) ($maintenances[$unit->id] ?? collect())
                    ->filter(fn (Maintenance $maintenance) => $maintenance->starts_on->toDateString() <= $date && $maintenance->ends_on->toDateString() >= $date)
                    ->sum('quantity');

                $closure = $unitClosures[$date] ?? null;
                $blocked = $closure?->is_closed ? $capacity : min($capacity, $inMaintenance + (int) $closure?->blocked_quantity);

                $used = $booked + $blocked;

                $cells[$date] = [
                    'booked' => $booked,
                    'blocked' => $blocked,
                    'capacity' => $capacity,
                    'state' => match (true) {
                        $closure?->is_closed => 'closed',
                        $used >= $capacity => $booked > 0 ? 'full' : 'blocked',
                        $used > 0 => 'partial',
                        default => 'free',
                    },
                    'reservations' => $present->all(),
                    'starts' => $present->filter(fn (Reservation $reservation) => $reservation->check_in->toDateString() === $date)->values()->all(),
                ];
            }

            return ['unit' => $unit, 'cells' => $cells];
        });
    }

    /**
     * Taux d'occupation du mois (nuits réservées / nuits disponibles), en pourcentage.
     *
     * @param  Collection<int, array{unit: Unit, cells: array<string, array{booked: int, blocked: int, capacity: int}>}>  $rows
     */
    public static function occupancyRate(Collection $rows): float
    {
        $booked = 0;
        $available = 0;

        foreach ($rows as $row) {
            foreach ($row['cells'] as $cell) {
                $booked += $cell['booked'];
                $available += max(0, $cell['capacity'] - $cell['blocked']);
            }
        }

        return $available > 0 ? round($booked / $available * 100, 1) : 0.0;
    }
}
