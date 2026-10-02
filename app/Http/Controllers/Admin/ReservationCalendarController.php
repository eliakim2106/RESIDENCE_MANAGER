<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Property;
use App\Services\OccupancyCalendar;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Calendrier d'occupation : unités d'un établissement jour par jour, sur un mois.
 */
class ReservationCalendarController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $properties = Property::query()
            ->unless($user->isAdmin(), fn (Builder $query) => $query->ownedBy($user))
            ->orderBy('name')
            ->get(['id', 'name', 'slug']);

        $property = $properties->firstWhere('slug', (string) $request->query('etablissement')) ?? $properties->first();
        $month = $this->month((string) $request->query('mois'));

        $calendar = $property ? new OccupancyCalendar($property, $month) : null;
        $rows = $calendar?->rows() ?? collect();
        $weeks = $calendar?->weeks($rows) ?? [];

        // Séjours qui occupent au moins une nuit du mois, dans l'ordre des arrivées (agenda mobile, chiffres)
        $stays = collect($weeks)->flatMap(fn (array $week) => array_column($week['bars'], 'reservation'))
            ->unique('id')
            ->filter(fn ($reservation) => $reservation->check_in->lt($month->endOfMonth()) && $reservation->check_out->gt($month->startOfMonth()))
            ->sortBy('check_in')
            ->values();
        $monthDays = collect($weeks)->flatMap(fn (array $week) => $week['days'])->where('inMonth', true);

        return view('admin.reservations.calendar', [
            'properties' => $properties,
            'property' => $property,
            'calendar' => $calendar,
            'rows' => $rows,
            'weeks' => $weeks,
            'stays' => $stays,
            'view' => $request->query('vue') === 'planning' ? 'planning' : 'mois',
            'month' => $month,
            'occupancy' => OccupancyCalendar::occupancyRate($rows),
            'summary' => [
                'stays' => $stays->count(),
                'arrivals' => $monthDays->sum('arrivals'),
                'departures' => $monthDays->sum('departures'),
                'nights' => $monthDays->sum('booked'),
            ],
        ]);
    }

    /**
     * Mois demandé (AAAA-MM), sinon le mois en cours.
     */
    private function month(string $value): CarbonImmutable
    {
        if (preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $value)) {
            return CarbonImmutable::createFromFormat('!Y-m', $value);
        }

        return CarbonImmutable::today()->startOfMonth();
    }
}
