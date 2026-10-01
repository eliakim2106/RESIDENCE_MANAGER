<?php

namespace App\Services;

use App\Enums\ActiveStatus;
use App\Enums\ReservationStatus;
use App\Models\Property;
use App\Models\ReservationUnit;
use App\Models\Unit;
use App\Models\UnitType;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\LazyCollection;

/**
 * Liste des unités : onglets, recherche, filtres (établissement, type), tri, synthèse et export.
 * Un propriétaire ne voit que les unités de ses établissements.
 */
class UnitListing
{
    public const TABS = ['tous' => 'Toutes', 'actifs' => 'Actives', 'inactifs' => 'Inactives'];

    public const SORTS = [
        'recentes' => 'Plus récentes',
        'nom' => 'Nom (A → Z)',
        'prix-croissant' => 'Prix croissant',
        'prix-decroissant' => 'Prix décroissant',
        'capacite' => 'Capacité',
        'reservations' => 'Réservations à venir',
    ];

    public readonly string $tab;

    public readonly string $search;

    public readonly string $sort;

    public readonly ?Property $property;

    public readonly ?UnitType $type;

    public function __construct(private readonly User $user, Request $request)
    {
        $this->tab = array_key_exists((string) $request->query('statut'), self::TABS) ? (string) $request->query('statut') : 'tous';
        $this->search = trim((string) $request->query('search'));
        $this->sort = array_key_exists((string) $request->query('tri'), self::SORTS) ? (string) $request->query('tri') : 'recentes';
        $this->property = $request->filled('etablissement') ? $this->properties()->firstWhere('slug', (string) $request->query('etablissement')) : null;
        $this->type = $request->filled('type') ? UnitType::query()->where('slug', $request->query('type'))->first() : null;
    }

    public function paginate(int $perPage = 12): LengthAwarePaginator
    {
        return $this->query()->paginate($perPage)->withQueryString();
    }

    /**
     * Lignes de l'export (mêmes filtres et même tri que la page).
     *
     * @return LazyCollection<int, Unit>
     */
    public function export(): LazyCollection
    {
        return $this->query()->lazy(200);
    }

    /**
     * @return array<string, int>
     */
    public function counts(): array
    {
        $base = $this->filtered();
        $active = (clone $base)->where('statut', ActiveStatus::Active)->count();
        $all = (clone $base)->count();

        return ['tous' => $all, 'actifs' => $active, 'inactifs' => $all - $active];
    }

    /**
     * @return array{units: int, capacity: int, averagePrice: int, upcoming: int}
     */
    public function summary(): array
    {
        $active = $this->scoped()->where('statut', ActiveStatus::Active)->get(['id', 'quantity', 'max_adults', 'max_children', 'base_price', 'promo_price']);

        return [
            'units' => (int) $active->sum('quantity'),
            'capacity' => (int) $active->sum(fn (Unit $unit) => ($unit->max_adults + $unit->max_children) * $unit->quantity),
            'averagePrice' => (int) round($active->avg(fn (Unit $unit) => $unit->promo_price ?: $unit->base_price) ?? 0),
            'upcoming' => ReservationUnit::query()
                ->whereIn('unit_id', $this->scoped()->select('id'))
                ->whereHas('reservation', fn (Builder $query) => $query
                    ->whereIn('statut', [ReservationStatus::Pending, ReservationStatus::Confirmed])
                    ->whereDate('check_out', '>=', CarbonImmutable::today()->toDateString()))
                ->distinct()
                ->count('reservation_id'),
        ];
    }

    /**
     * Établissements proposés dans le filtre.
     *
     * @return Collection<int, Property>
     */
    public function properties(): Collection
    {
        return Property::query()
            ->unless($this->user->isAdmin(), fn (Builder $query) => $query->ownedBy($this->user))
            ->has('units')
            ->orderBy('name')
            ->get(['id', 'name', 'slug']);
    }

    /**
     * @return Collection<int, UnitType>
     */
    public function types(): Collection
    {
        return UnitType::query()->whereIn('id', $this->scoped()->select('unit_type_id'))->orderBy('name')->get();
    }

    public function hasFilters(): bool
    {
        return $this->search !== '' || $this->property || $this->type;
    }

    /*
    |--------------------------------------------------------------------------
    | REQUÊTES
    |--------------------------------------------------------------------------
    */

    /**
     * @return Builder<Unit>
     */
    private function query(): Builder
    {
        $query = $this->filtered()
            ->when($this->tab === 'actifs', fn (Builder $query) => $query->where('statut', ActiveStatus::Active))
            ->when($this->tab === 'inactifs', fn (Builder $query) => $query->where('statut', '!=', ActiveStatus::Active))
            ->with(['unitType', 'property.city', 'images'])
            ->withCount(['reservationUnits as upcoming_count' => fn (Builder $query) => $query->whereHas('reservation', fn (Builder $query) => $query
                ->whereIn('statut', [ReservationStatus::Pending, ReservationStatus::Confirmed])
                ->whereDate('check_out', '>=', CarbonImmutable::today()->toDateString()))]);

        return match ($this->sort) {
            'nom' => $query->orderBy('name'),
            'prix-croissant' => $query->orderByRaw('COALESCE(promo_price, base_price) asc'),
            'prix-decroissant' => $query->orderByRaw('COALESCE(promo_price, base_price) desc'),
            'capacite' => $query->orderByRaw('(max_adults + max_children) desc'),
            'reservations' => $query->orderByDesc('upcoming_count'),
            default => $query->latest('id'),
        };
    }

    /**
     * @return Builder<Unit>
     */
    private function scoped(): Builder
    {
        return Unit::query()->whereHas('property', fn (Builder $query) => $query->unless($this->user->isAdmin(), fn (Builder $query) => $query->ownedBy($this->user)));
    }

    /**
     * @return Builder<Unit>
     */
    private function filtered(): Builder
    {
        $like = '%'.addcslashes($this->search, '%_\\').'%';

        return $this->scoped()
            ->when($this->search !== '', fn (Builder $query) => $query->where(fn (Builder $query) => $query
                ->where('name', 'like', $like)
                ->orWhereHas('property', fn (Builder $query) => $query->where('name', 'like', $like))))
            ->when($this->property, fn (Builder $query) => $query->whereBelongsTo($this->property))
            ->when($this->type, fn (Builder $query) => $query->whereBelongsTo($this->type, 'unitType'));
    }
}
