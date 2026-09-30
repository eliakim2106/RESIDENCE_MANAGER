<?php

namespace App\Services;

use App\Enums\ActiveStatus;
use App\Enums\MaintenanceStatus;
use App\Enums\PropertyStatus;
use App\Models\Availability;
use App\Models\City;
use App\Models\Equipment;
use App\Models\Maintenance;
use App\Models\Property;
use App\Models\PropertyType;
use App\Models\Reservation;
use App\Models\ReservationUnit;
use App\Models\Unit;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;

/**
 * Recherche des établissements publiés pour la page « Nos résidences ».
 *
 * Les paramètres reprennent ceux de la recherche de l'accueil (destination, arrivee, depart, voyageurs),
 * complétés par les filtres de la page. Une valeur invalide est ignorée plutôt que de provoquer une erreur.
 *
 * Un établissement est retenu s'il possède au moins une unité active qui satisfait à la fois la capacité,
 * le budget et la disponibilité : le prix affiché est celui de l'unité la moins chère parmi celles-ci.
 */
class ResidenceSearch
{
    public const PER_PAGE = 9;

    public const MAX_GUESTS = 16;

    /**
     * @var array<string, string>
     */
    public const SORTS = [
        'recommandees' => 'Recommandées',
        'prix-croissant' => 'Prix croissant',
        'prix-decroissant' => 'Prix décroissant',
        'note' => 'Mieux notées',
        'recentes' => 'Nouveautés',
    ];

    /**
     * Note minimale sur 10, comme les avis.
     *
     * @var array<int, string>
     */
    public const RATINGS = [
        9 => 'Exceptionnel',
        8 => 'Très bien',
        7 => 'Bien',
    ];

    /**
     * @param  array{destination: ?string, arrivee: ?Carbon, depart: ?Carbon, voyageurs: ?int, villes: list<string>, types: list<string>, equipements: list<string>, prix_min: ?int, prix_max: ?int, note: ?int, tri: string}  $filters
     */
    public function __construct(public readonly array $filters) {}

    public static function fromRequest(Request $request): self
    {
        [$arrival, $departure] = self::stay($request->query('arrivee'), $request->query('depart'));

        $guests = filter_var($request->query('voyageurs'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => self::MAX_GUESTS]]);
        $minPrice = filter_var($request->query('prix_min'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
        $maxPrice = filter_var($request->query('prix_max'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
        $rating = filter_var($request->query('note'), FILTER_VALIDATE_INT);
        $sort = (string) $request->query('tri');

        return new self([
            'destination' => self::text($request->query('destination')),
            'arrivee' => $arrival,
            'depart' => $departure,
            'voyageurs' => $guests === false ? null : $guests,
            'villes' => self::slugs($request->query('villes')),
            'types' => self::slugs($request->query('types')),
            'equipements' => self::slugs($request->query('equipements')),
            'prix_min' => $minPrice === false || $minPrice === 0 ? null : $minPrice,
            'prix_max' => $maxPrice === false || $maxPrice === 0 ? null : $maxPrice,
            'note' => array_key_exists((int) $rating, self::RATINGS) ? (int) $rating : null,
            'tri' => array_key_exists($sort, self::SORTS) ? $sort : 'recommandees',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | RÉSULTATS
    |--------------------------------------------------------------------------
    */

    public function paginate(): LengthAwarePaginator
    {
        return $this->query()
            ->paginate(self::PER_PAGE)
            ->withQueryString();
    }

    /**
     * @return Builder<Property>
     */
    public function query(): Builder
    {
        $filters = $this->filters;
        $matchingUnits = fn (Builder $units) => $this->constrainUnits($units);

        $query = Property::query()
            ->published()
            ->select('properties.*')
            ->addSelect([
                // Prix de la nuit le plus bas parmi les unités qui répondent à la recherche
                'prix_min' => Unit::query()
                    ->selectRaw('MIN(COALESCE(promo_price, base_price))')
                    ->whereColumn('units.property_id', 'properties.id')
                    ->where($matchingUnits),
                'capacite_max' => Unit::query()
                    ->selectRaw('MAX(max_adults + max_children)')
                    ->whereColumn('units.property_id', 'properties.id')
                    ->where('statut', ActiveStatus::Active),
                'en_promo' => Unit::query()
                    ->selectRaw('COUNT(*)')
                    ->whereColumn('units.property_id', 'properties.id')
                    ->whereNotNull('promo_price')
                    ->where($matchingUnits),
            ])
            ->whereHas('units', $matchingUnits)
            ->with([
                'city',
                'propertyType',
                'coverImage',
                'equipments' => fn ($query) => $query->active()->where('is_popular', true)->orderBy('name'),
            ])
            ->when($filters['destination'], function (Builder $query, string $destination): void {
                $like = '%'.addcslashes($destination, '%_\\').'%';

                $query->where(fn (Builder $query) => $query
                    ->where('properties.name', 'like', $like)
                    ->orWhere('district', 'like', $like)
                    ->orWhere('neighborhood', 'like', $like)
                    ->orWhereHas('city', fn (Builder $query) => $query->where('name', 'like', $like)));
            })
            ->when($filters['villes'], fn (Builder $query, array $slugs) => $query->whereHas('city', fn (Builder $query) => $query->whereIn('slug', $slugs)))
            ->when($filters['types'], fn (Builder $query, array $slugs) => $query->whereHas('propertyType', fn (Builder $query) => $query->whereIn('slug', $slugs)))
            ->when($filters['note'], fn (Builder $query, int $rating) => $query->where('rating_average', '>=', $rating));

        // Chaque équipement demandé doit être proposé par l'établissement ou par l'une de ses unités
        foreach ($filters['equipements'] as $slug) {
            $query->where(fn (Builder $query) => $query
                ->whereHas('equipments', fn (Builder $query) => $query->where('slug', $slug))
                ->orWhereHas('units.equipments', fn (Builder $query) => $query->where('slug', $slug)));
        }

        return $this->sort($query);
    }

    /**
     * Unités actives qui répondent à la capacité, au budget et aux dates demandés.
     *
     * @param  Builder<Unit>  $units
     */
    private function constrainUnits(Builder $units): void
    {
        $filters = $this->filters;

        $units->where('units.statut', ActiveStatus::Active)
            ->when($filters['voyageurs'], fn (Builder $query, int $guests) => $query->whereRaw('max_adults + max_children >= ?', [$guests]))
            ->when($filters['prix_min'], fn (Builder $query, int $price) => $query->whereRaw('COALESCE(promo_price, base_price) >= ?', [$price]))
            ->when($filters['prix_max'], fn (Builder $query, int $price) => $query->whereRaw('COALESCE(promo_price, base_price) <= ?', [$price]));

        if ($filters['arrivee'] && $filters['depart']) {
            $this->constrainAvailability($units, $filters['arrivee'], $filters['depart']);
        }
    }

    /**
     * Disponibilité sur la période [arrivée, départ[.
     *
     * Calcul prudent : la quantité de l'unité doit dépasser le total des réservations qui chevauchent la période,
     * plus le plus fort blocage manuel d'une nuit et les maintenances en cours. Une unité réservée sur des nuits
     * distinctes peut être écartée à tort, mais une unité complète n'est jamais proposée.
     *
     * @param  Builder<Unit>  $units
     */
    private function constrainAvailability(Builder $units, Carbon $arrival, Carbon $departure): void
    {
        $lastNight = $departure->copy()->subDay();

        $booked = ReservationUnit::query()
            ->selectRaw('COALESCE(SUM(reservation_units.quantity), 0)')
            ->whereColumn('reservation_units.unit_id', 'units.id')
            ->whereIn('reservation_id', Reservation::query()->select('id')->overlapping($arrival, $departure)->blocking());

        $blocked = Availability::query()
            ->selectRaw('COALESCE(MAX(blocked_quantity), 0)')
            ->whereColumn('availabilities.unit_id', 'units.id')
            ->whereBetween('date', [$arrival->toDateString(), $lastNight->toDateString()]);

        $inMaintenance = Maintenance::query()
            ->selectRaw('COALESCE(SUM(maintenances.quantity), 0)')
            ->whereColumn('maintenances.unit_id', 'units.id')
            ->where('statut', '!=', MaintenanceStatus::Done)
            ->where('starts_on', '<=', $lastNight->toDateString())
            ->where('ends_on', '>=', $arrival->toDateString());

        $units
            ->whereDoesntHave('availabilities', fn (Builder $query) => $query
                ->where('is_closed', true)
                ->whereBetween('date', [$arrival->toDateString(), $lastNight->toDateString()]))
            ->whereRaw(
                'units.quantity > ('.$booked->toSql().') + ('.$blocked->toSql().') + ('.$inMaintenance->toSql().')',
                [...$booked->getBindings(), ...$blocked->getBindings(), ...$inMaintenance->getBindings()],
            );
    }

    /**
     * @param  Builder<Property>  $query
     * @return Builder<Property>
     */
    private function sort(Builder $query): Builder
    {
        return match ($this->filters['tri']) {
            'prix-croissant' => $query->orderBy('prix_min')->orderByDesc('rating_average'),
            'prix-decroissant' => $query->orderByDesc('prix_min')->orderByDesc('rating_average'),
            'note' => $query->orderByDesc('rating_average')->orderByDesc('reviews_count'),
            'recentes' => $query->orderByDesc('published_at')->orderByDesc('properties.id'),
            default => $query->orderByDesc('is_featured')->orderByDesc('rating_average')->orderByDesc('reviews_count'),
        };
    }

    /*
    |--------------------------------------------------------------------------
    | FACETTES ET FILTRES ACTIFS
    |--------------------------------------------------------------------------
    */

    /**
     * Villes qui comptent au moins un établissement publié, avec leur nombre.
     *
     * @return Collection<int, City>
     */
    public function cities(): Collection
    {
        return City::active()
            ->withCount(['properties' => fn (Builder $query) => $query->where('statut', PropertyStatus::Published)])
            ->orderBy('name')
            ->get()
            ->filter(fn (City $city): bool => $city->properties_count > 0)
            ->sortByDesc('properties_count')
            ->values();
    }

    /**
     * @return Collection<int, PropertyType>
     */
    public function propertyTypes(): Collection
    {
        return PropertyType::active()
            ->withCount(['properties' => fn (Builder $query) => $query->where('statut', PropertyStatus::Published)])
            ->orderBy('name')
            ->get()
            ->filter(fn (PropertyType $type): bool => $type->properties_count > 0)
            ->values();
    }

    /**
     * @return Collection<int, Equipment>
     */
    public function equipments(): Collection
    {
        return Equipment::active()->where('is_popular', true)->orderBy('name')->get();
    }

    /**
     * Bornes du curseur de budget : prix de la nuit le plus bas et le plus haut des établissements publiés.
     *
     * @return array{0: int, 1: int}
     */
    public function priceBounds(): array
    {
        $bounds = Unit::query()
            ->where('statut', ActiveStatus::Active)
            ->whereHas('property', fn (Builder $query) => $query->published())
            ->selectRaw('MIN(COALESCE(promo_price, base_price)) AS min_price, MAX(COALESCE(promo_price, base_price)) AS max_price')
            ->first();

        $min = (int) floor(((int) $bounds?->min_price) / 5000) * 5000;
        $max = (int) ceil(((int) $bounds?->max_price) / 5000) * 5000;

        return [$min, max($max, $min + 5000)];
    }

    public function nights(): ?int
    {
        return $this->filters['arrivee'] && $this->filters['depart']
            ? (int) $this->filters['arrivee']->diffInDays($this->filters['depart'])
            : null;
    }

    /**
     * Filtres appliqués, chacun avec l'adresse de la recherche sans lui (pour les pastilles « × »).
     *
     * @param  Collection<int, City>  $cities
     * @param  Collection<int, PropertyType>  $types
     * @param  Collection<int, Equipment>  $equipments
     * @return list<array{label: string, url: string}>
     */
    public function activeFilters(Collection $cities, Collection $types, Collection $equipments): array
    {
        $query = request()->query();
        $without = fn (string $key, ?string $value = null): string => route('residences.index', array_filter(
            $value === null ? Arr::except($query, [$key, 'page']) : [...Arr::except($query, ['page']), $key => array_values(array_diff((array) ($query[$key] ?? []), [$value]))],
            fn ($item) => $item !== null && $item !== '' && $item !== [],
        ));

        $chips = [];
        $filters = $this->filters;

        if ($filters['destination']) {
            $chips[] = ['label' => '« '.$filters['destination'].' »', 'url' => $without('destination')];
        }

        if ($filters['arrivee'] && $filters['depart']) {
            $chips[] = [
                'label' => $filters['arrivee']->translatedFormat('j M').' → '.$filters['depart']->translatedFormat('j M'),
                'url' => route('residences.index', Arr::except($query, ['arrivee', 'depart', 'page'])),
            ];
        }

        if ($filters['voyageurs']) {
            $chips[] = ['label' => $filters['voyageurs'].' voyageur'.($filters['voyageurs'] > 1 ? 's' : ''), 'url' => $without('voyageurs')];
        }

        foreach ($filters['villes'] as $slug) {
            $chips[] = ['label' => $cities->firstWhere('slug', $slug)->name ?? $slug, 'url' => $without('villes', $slug)];
        }

        foreach ($filters['types'] as $slug) {
            $chips[] = ['label' => $types->firstWhere('slug', $slug)->name ?? $slug, 'url' => $without('types', $slug)];
        }

        if ($filters['prix_min'] || $filters['prix_max']) {
            $chips[] = [
                'label' => match (true) {
                    $filters['prix_min'] && $filters['prix_max'] => self::money($filters['prix_min']).' – '.self::money($filters['prix_max']),
                    (bool) $filters['prix_min'] => 'Dès '.self::money($filters['prix_min']),
                    default => 'Jusqu’à '.self::money($filters['prix_max']),
                },
                'url' => route('residences.index', Arr::except($query, ['prix_min', 'prix_max', 'page'])),
            ];
        }

        foreach ($filters['equipements'] as $slug) {
            $chips[] = ['label' => $equipments->firstWhere('slug', $slug)->name ?? $slug, 'url' => $without('equipements', $slug)];
        }

        if ($filters['note']) {
            $chips[] = ['label' => 'Note '.$filters['note'].'+', 'url' => $without('note')];
        }

        return $chips;
    }

    public static function money(int $amount): string
    {
        return number_format($amount, 0, ',', ' ').' FCFA';
    }

    /*
    |--------------------------------------------------------------------------
    | LECTURE DES PARAMÈTRES
    |--------------------------------------------------------------------------
    */

    private static function text(mixed $value): ?string
    {
        $text = is_string($value) ? trim(mb_substr($value, 0, 100)) : '';

        return $text === '' ? null : $text;
    }

    /**
     * @return list<string>
     */
    private static function slugs(mixed $value): array
    {
        return array_values(array_unique(array_filter(
            (array) $value,
            fn ($slug) => is_string($slug) && preg_match('/^[a-z0-9-]{1,100}$/', $slug) === 1,
        )));
    }

    /**
     * Séjour valide : arrivée à partir d'aujourd'hui, départ au moins le lendemain (une nuit par défaut).
     *
     * @return array{0: ?Carbon, 1: ?Carbon}
     */
    private static function stay(mixed $arrival, mixed $departure): array
    {
        $parse = function (mixed $value): ?Carbon {
            if (! is_string($value) || preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) !== 1) {
                return null;
            }

            $date = Carbon::createFromFormat('!Y-m-d', $value);

            return $date && $date->format('Y-m-d') === $value ? $date : null;
        };

        $from = $parse($arrival);
        $to = $parse($departure);

        if ($from === null || $from->lt(today())) {
            return [null, null];
        }

        if ($to === null || $to->lte($from)) {
            $to = $from->copy()->addDay();
        }

        return [$from, $to->diffInDays($from, true) > 60 ? $from->copy()->addDays(60) : $to];
    }
}
