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

        return view('admin.reservations.calendar', [
            'properties' => $properties,
            'property' => $property,
            'calendar' => $calendar,
            'rows' => $rows,
            'month' => $month,
            'occupancy' => OccupancyCalendar::occupancyRate($rows),
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
