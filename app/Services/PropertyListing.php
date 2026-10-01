<?php

namespace App\Services;

use App\Enums\ActiveStatus;
use App\Enums\PropertyStatus;
use App\Enums\ReservationStatus;
use App\Enums\TransactionStatus;
use App\Enums\UserRole;
use App\Models\City;
use App\Models\Payment;
use App\Models\Property;
use App\Models\PropertyType;
use App\Models\Unit;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Liste des établissements : onglets par statut, recherche, filtres (ville, type, propriétaire), tri,
 * indicateurs de chaque établissement et synthèse. Un propriétaire ne voit que les siens.
 */
class PropertyListing
{
    /**
     * Onglets : libellé et condition sur le statut.
     *
     * @var array<string, string>
     */
    public const TABS = [
        'tous' => 'Tous',
        'publies' => 'Publiés',
        'en-attente' => 'À valider',
        'brouillons' => 'Brouillons',
        'refuses' => 'Refusés',
        'suspendus' => 'Suspendus',
    ];

    /**
     * Tris proposés : libellé.
     *
     * @var array<string, string>
     */
    public const SORTS = [
        'recents' => 'Plus récents',
        'nom' => 'Nom (A → Z)',
        'revenus' => 'Revenus du mois',
        'note' => 'Mieux notés',
        'reservations' => 'Réservations à venir',
    ];

    public readonly string $tab;

    public readonly string $search;

    public readonly string $sort;

    public readonly ?City $city;

    public readonly ?PropertyType $type;

    public readonly ?User $owner;

    private readonly CarbonImmutable $monthStart;

    public function __construct(private readonly User $user, Request $request)
    {
        $this->tab = array_key_exists((string) $request->query('statut'), self::TABS) ? (string) $request->query('statut') : 'tous';
        $this->search = trim((string) $request->query('search'));
        $this->sort = array_key_exists((string) $request->query('tri'), self::SORTS) ? (string) $request->query('tri') : 'recents';
        $this->city = $request->filled('ville') ? City::query()->where('slug', $request->query('ville'))->first() : null;
        $this->type = $request->filled('type') ? PropertyType::query()->where('slug', $request->query('type'))->first() : null;
        $this->owner = $user->isAdmin() && $request->filled('proprietaire')
            ? User::query()->where('role', UserRole::Owner)->find((int) $request->query('proprietaire'))
            : null;
        $this->monthStart = CarbonImmutable::now()->startOfMonth();
    }

    /**
     * Établissements de la page, avec leurs indicateurs (unités, réservations à venir, revenus du mois).
     */
    public function paginate(int $perPage = 10): LengthAwarePaginator
    {
        $query = $this->filtered();
        $this->applyTab($query, $this->tab);

        $query->with(['propertyType', 'city', 'coverImage', 'owner'])
            ->withCount([
                'units',
                'reservations as upcoming_count' => fn (Builder $query) => $query
                    ->whereIn('statut', [ReservationStatus::Pending, ReservationStatus::Confirmed])
                    ->whereDate('check_out', '>=', CarbonImmutable::today()->toDateString()),
            ])
            ->addSelect(['month_revenue' => $this->revenueSubquery()]);

        match ($this->sort) {
            'nom' => $query->orderBy('name'),
            'revenus' => $query->orderByDesc('month_revenue')->orderBy('name'),
            'note' => $query->orderByDesc('rating_average')->orderByDesc('reviews_count'),
            'reservations' => $query->orderByDesc('upcoming_count')->orderBy('name'),
            default => $query->latest('id'),
        };

        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * Nombre d'établissements par onglet (avec la recherche et les filtres en cours).
     *
     * @return array<string, int>
     */
    public function counts(): array
    {
        return collect(array_keys(self::TABS))
            ->mapWithKeys(function (string $tab): array {
                $query = $this->filtered();
                $this->applyTab($query, $tab);

                return [$tab => $query->count()];
            })
            ->all();
    }

    /**
     * Synthèse affichée en haut de page (sur tous les établissements visibles, sans filtre).
     *
     * @return array{published: int, pending: int, drafts: int, units: int, revenue: int}
     */
    public function summary(): array
    {
        $byStatus = $this->scoped()->selectRaw('statut, COUNT(*) as total')->groupBy('statut')->pluck('total', 'statut');

        return [
            'published' => (int) ($byStatus[PropertyStatus::Published->value] ?? 0),
            'pending' => (int) ($byStatus[PropertyStatus::Pending->value] ?? 0),
            'drafts' => (int) ($byStatus[PropertyStatus::Draft->value] ?? 0),
            'units' => (int) Unit::query()->where('statut', ActiveStatus::Active)->whereIn('property_id', $this->scoped()->select('id'))->sum('quantity'),
            'revenue' => (int) Payment::query()
                ->where('statut', TransactionStatus::Accepted)
                ->where('paid_at', '>=', $this->monthStart)
                ->whereHas('reservation', fn (Builder $query) => $query->whereIn('property_id', $this->scoped()->select('id')))
                ->sum(DB::raw('amount - refunded_amount')),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | OPTIONS DES FILTRES
    |--------------------------------------------------------------------------
    */

    /**
     * @return Collection<int, City>
     */
    public function cities(): Collection
    {
        return City::query()->whereIn('id', $this->scoped()->select('city_id'))->orderBy('name')->get();
    }

    /**
     * @return Collection<int, PropertyType>
     */
    public function types(): Collection
    {
        return PropertyType::query()->whereIn('id', $this->scoped()->select('property_type_id'))->orderBy('name')->get();
    }

    /**
     * Propriétaires (administrateurs uniquement).
     *
     * @return Collection<int, User>
     */
    public function owners(): Collection
    {
        return $this->user->isAdmin()
            ? User::query()->where('role', UserRole::Owner)->whereHas('properties')->orderBy('name')->get(['id', 'name'])
            : collect();
    }

    public function hasFilters(): bool
    {
        return $this->search !== '' || $this->city || $this->type || $this->owner;
    }

    /*
    |--------------------------------------------------------------------------
    | REQUÊTES
    |--------------------------------------------------------------------------
    */

    /**
     * @return Builder<Property>
     */
    private function scoped(): Builder
    {
        return Property::query()->unless($this->user->isAdmin(), fn (Builder $query) => $query->ownedBy($this->user));
    }

    /**
     * @return Builder<Property>
     */
    private function filtered(): Builder
    {
        $like = '%'.addcslashes($this->search, '%_\\').'%';

        return $this->scoped()
            ->when($this->search !== '', fn (Builder $query) => $query->where(fn (Builder $query) => $query
                ->where('name', 'like', $like)
                ->orWhere('district', 'like', $like)
                ->orWhere('neighborhood', 'like', $like)
                ->orWhereHas('city', fn (Builder $query) => $query->where('name', 'like', $like))
                ->orWhereHas('owner', fn (Builder $query) => $query->where('name', 'like', $like))))
            ->when($this->city, fn (Builder $query) => $query->whereBelongsTo($this->city))
            ->when($this->type, fn (Builder $query) => $query->whereBelongsTo($this->type, 'propertyType'))
            ->when($this->owner, fn (Builder $query) => $query->whereBelongsTo($this->owner, 'owner'));
    }

    /**
     * @param  Builder<Property>  $query
     */
    private function applyTab(Builder $query, string $tab): void
    {
        match ($tab) {
            'publies' => $query->where('statut', PropertyStatus::Published),
            'en-attente' => $query->where('statut', PropertyStatus::Pending),
            'brouillons' => $query->where('statut', PropertyStatus::Draft)->where(fn (Builder $query) => $query->whereNull('moderation_note')->orWhere('moderation_note', '')),
            'refuses' => $query->where('statut', PropertyStatus::Draft)->whereNotNull('moderation_note')->where('moderation_note', '!=', ''),
            'suspendus' => $query->where('statut', PropertyStatus::Suspended),
            default => null,
        };
    }

    /**
     * Revenus encaissés du mois en cours (net des remboursements), pour chaque établissement.
     *
     * @return Builder<Payment>
     */
    private function revenueSubquery(): Builder
    {
        return Payment::query()
            ->selectRaw('COALESCE(SUM(payments.amount - payments.refunded_amount), 0)')
            ->join('reservations', 'reservations.id', '=', 'payments.reservation_id')
            ->whereColumn('reservations.property_id', 'properties.id')
            ->where('payments.statut', TransactionStatus::Accepted)
            ->where('payments.paid_at', '>=', $this->monthStart);
    }
}
