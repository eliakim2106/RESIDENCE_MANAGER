<?php

namespace App\Services;

use App\Enums\ActiveStatus;
use App\Enums\ContactMessageStatus;
use App\Enums\PropertyStatus;
use App\Enums\ReservationStatus;
use App\Enums\ReviewStatus;
use App\Enums\TransactionStatus;
use App\Enums\UserRole;
use App\Models\ContactMessage;
use App\Models\Payment;
use App\Models\Property;
use App\Models\Reservation;
use App\Models\Review;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Indicateurs du tableau de bord.
 *
 * Gestionnaire (administrateur ou propriétaire) : revenus, réservations, occupation, notes, tâches à traiter.
 * Le périmètre est toute la plateforme pour un administrateur, ses propres établissements pour un propriétaire.
 * Client : ses séjours et ses réservations.
 */
class DashboardStats
{
    private readonly bool $scoped;

    private readonly Carbon $monthStart;

    public function __construct(private readonly User $user)
    {
        $this->scoped = ! $user->isAdmin();
        $this->monthStart = now()->startOfMonth();
    }

    /*
    |--------------------------------------------------------------------------
    | PÉRIMÈTRE
    |--------------------------------------------------------------------------
    */

    /**
     * @return Builder<Property>
     */
    private function properties(): Builder
    {
        return Property::query()->when($this->scoped, fn (Builder $query) => $query->ownedBy($this->user));
    }

    /**
     * @return Builder<Reservation>
     */
    private function reservations(): Builder
    {
        return Reservation::query()->when($this->scoped, fn (Builder $query) => $query->whereIn('property_id', $this->properties()->select('id')));
    }

    /**
     * @return Builder<Payment>
     */
    private function acceptedPayments(): Builder
    {
        return Payment::query()
            ->where('statut', TransactionStatus::Accepted)
            ->whereIn('reservation_id', $this->reservations()->select('id'));
    }

    /*
    |--------------------------------------------------------------------------
    | GESTIONNAIRE : INDICATEURS CLÉS
    |--------------------------------------------------------------------------
    */

    /**
     * @return array{revenue: array{value: int, previous: int, trend: ?float}, bookings: array{value: int, previous: int, trend: ?float}, occupancy: float, rating: array{value: ?float, reviews: int}}
     */
    public function kpis(): array
    {
        $previousStart = $this->monthStart->copy()->subMonth();

        $revenue = $this->revenueBetween($this->monthStart, $this->monthStart->copy()->endOfMonth());
        $previousRevenue = $this->revenueBetween($previousStart, $previousStart->copy()->endOfMonth());

        // Séjours qui commencent dans le mois, hors annulations
        $bookings = $this->staysStartingIn($this->monthStart);
        $previousBookings = $this->staysStartingIn($previousStart);

        $rated = $this->properties()->where('reviews_count', '>', 0);

        return [
            'revenue' => ['value' => $revenue, 'previous' => $previousRevenue, 'trend' => self::trend($revenue, $previousRevenue)],
            'bookings' => ['value' => $bookings, 'previous' => $previousBookings, 'trend' => self::trend($bookings, $previousBookings)],
            'occupancy' => $this->occupancyRate(),
            'rating' => [
                'value' => (clone $rated)->count() > 0 ? round((float) (clone $rated)->avg('rating_average'), 1) : null,
                'reviews' => (int) $this->properties()->sum('reviews_count'),
            ],
        ];
    }

    /**
     * Chiffres de contexte affichés sous les indicateurs clés.
     *
     * @return list<array{label: string, value: int, hint: string, icon: string}>
     */
    public function overview(): array
    {
        $published = $this->properties()->where('statut', PropertyStatus::Published)->count();
        $total = $this->properties()->count();
        $units = (int) Unit::query()
            ->where('statut', ActiveStatus::Active)
            ->whereIn('property_id', $this->properties()->select('id'))
            ->sum('quantity');

        $items = [
            ['label' => 'Établissements', 'value' => $total, 'hint' => $published.' publié'.($published > 1 ? 's' : ''), 'icon' => 'fa-building'],
            ['label' => 'Unités actives', 'value' => $units, 'hint' => 'chambres et logements', 'icon' => 'fa-door-open'],
        ];

        if ($this->scoped) {
            $clients = $this->reservations()->whereNotNull('user_id')->distinct()->count('user_id');

            $items[] = ['label' => 'Clients', 'value' => $clients, 'hint' => 'ayant réservé', 'icon' => 'fa-users'];
            $items[] = ['label' => 'Réservations', 'value' => $this->reservations()->count(), 'hint' => 'depuis le début', 'icon' => 'fa-calendar-check'];
        } else {
            $items[] = ['label' => 'Clients', 'value' => User::withRole(UserRole::Client)->count(), 'hint' => 'inscrits', 'icon' => 'fa-users'];
            $items[] = ['label' => 'Propriétaires', 'value' => User::withRole(UserRole::Owner)->count(), 'hint' => 'partenaires', 'icon' => 'fa-user-tie'];
        }

        return $items;
    }

    /**
     * Revenus encaissés sur les 6 derniers mois, mois en cours compris.
     *
     * @return list<array{label: string, long: string, value: int, current: bool}>
     */
    public function monthlyRevenue(int $months = 6): array
    {
        $start = $this->monthStart->copy()->subMonths($months - 1);

        $payments = $this->acceptedPayments()
            ->whereBetween('paid_at', [$start, now()->endOfMonth()])
            ->get(['amount', 'refunded_amount', 'paid_at'])
            ->groupBy(fn (Payment $payment) => $payment->paid_at->format('Y-m'));

        return collect(range(0, $months - 1))
            ->map(function (int $offset) use ($start, $payments): array {
                $month = $start->copy()->addMonths($offset);

                return [
                    'label' => $month->translatedFormat('M'),
                    'long' => $month->translatedFormat('F Y'),
                    'value' => (int) ($payments->get($month->format('Y-m'))?->sum(fn (Payment $payment) => $payment->netAmount()) ?? 0),
                    'current' => $month->isSameMonth(now()),
                ];
            })
            ->all();
    }

    /**
     * Répartition des réservations par statut, dans l'ordre du parcours d'une réservation.
     *
     * @return list<array{statut: ReservationStatus, count: int}>
     */
    public function reservationsByStatus(): array
    {
        $counts = $this->reservations()
            ->selectRaw('statut, COUNT(*) as total')
            ->groupBy('statut')
            ->pluck('total', 'statut');

        return collect([ReservationStatus::Pending, ReservationStatus::Confirmed, ReservationStatus::Completed, ReservationStatus::Cancelled, ReservationStatus::NoShow])
            ->map(fn (ReservationStatus $status): array => ['statut' => $status, 'count' => (int) ($counts[$status->value] ?? 0)])
            ->filter(fn (array $row): bool => $row['count'] > 0)
            ->values()
            ->all();
    }

    /**
     * Tâches en attente, avec un lien quand une page permet de les traiter.
     *
     * @return list<array{label: string, count: int, icon: string, tone: string, url: ?string}>
     */
    public function tasks(): array
    {
        $tasks = [
            [
                'label' => 'Réservations à valider',
                'count' => $this->reservations()->where('statut', ReservationStatus::Pending)->count(),
                'icon' => 'fa-hourglass-half',
                'tone' => 'warning',
                'url' => route('admin.reservations.index', ['statut' => 'en-attente']),
            ],
            // Administrateur : demandes à valider. Propriétaire : brouillons (dont refus) à compléter et soumettre.
            $this->scoped ? [
                'label' => 'Établissements à soumettre',
                'count' => $this->properties()->where('statut', PropertyStatus::Draft)->count(),
                'icon' => 'fa-file-pen',
                'tone' => 'info',
                'url' => route('admin.etablissements.index', ['statut' => 'inactifs']),
            ] : [
                'label' => 'Établissements à valider',
                'count' => $this->properties()->where('statut', PropertyStatus::Pending)->count(),
                'icon' => 'fa-building-circle-check',
                'tone' => 'warning',
                'url' => route('admin.validations.index'),
            ],
        ];

        if (! $this->scoped) {
            $tasks[] = [
                'label' => 'Avis signalés',
                'count' => Review::where('statut', ReviewStatus::Approved)->whereNotNull('reported_at')->count(),
                'icon' => 'fa-flag',
                'tone' => 'warning',
                'url' => route('admin.avis.index', ['statut' => 'signales']),
            ];
            $tasks[] = [
                'label' => 'Messages non lus',
                'count' => ContactMessage::where('statut', ContactMessageStatus::New)->count(),
                'icon' => 'fa-envelope',
                'tone' => 'info',
                'url' => null,
            ];
        }

        return array_values(array_filter($tasks, fn (array $task): bool => $task['count'] > 0));
    }

    /**
     * Prochaines arrivées (confirmées ou en attente).
     *
     * @return Collection<int, Reservation>
     */
    public function upcomingArrivals(int $limit = 5): Collection
    {
        return $this->reservations()
            ->with(['property', 'user'])
            ->whereIn('statut', [ReservationStatus::Confirmed, ReservationStatus::Pending])
            ->where('check_in', '>=', today())
            ->orderBy('check_in')
            ->limit($limit)
            ->get();
    }

    /**
     * @return Collection<int, Reservation>
     */
    public function latestReservations(int $limit = 6): Collection
    {
        return $this->reservations()->with(['property', 'user'])->latest('id')->limit($limit)->get();
    }

    /**
     * Établissements classés par revenus encaissés (tous les établissements pour un propriétaire).
     *
     * @return Collection<int, Property>
     */
    public function propertyPerformance(?int $limit = 5): Collection
    {
        $revenue = Payment::query()
            ->selectRaw('COALESCE(SUM(payments.amount - payments.refunded_amount), 0)')
            ->join('reservations', 'reservations.id', '=', 'payments.reservation_id')
            ->whereColumn('reservations.property_id', 'properties.id')
            ->where('payments.statut', TransactionStatus::Accepted);

        return $this->properties()
            ->with(['city', 'coverImage'])
            ->withCount(['reservations' => fn (Builder $query) => $query->where('statut', '!=', ReservationStatus::Cancelled)])
            ->addSelect(['revenue' => $revenue])
            ->orderByDesc('revenue')
            ->when($limit, fn (Builder $query, int $limit) => $query->limit($limit))
            ->get();
    }

    /*
    |--------------------------------------------------------------------------
    | CLIENT
    |--------------------------------------------------------------------------
    */

    public function nextStay(): ?Reservation
    {
        return Reservation::query()
            ->whereBelongsTo($this->user)
            ->with(['property.coverImage', 'property.city'])
            ->whereIn('statut', [ReservationStatus::Confirmed, ReservationStatus::Pending])
            ->where('check_out', '>=', today())
            ->orderBy('check_in')
            ->first();
    }

    /**
     * @return list<array{label: string, value: string, icon: string}>
     */
    public function clientSummary(): array
    {
        $reservations = Reservation::query()->whereBelongsTo($this->user);

        $paid = (int) Payment::query()
            ->where('statut', TransactionStatus::Accepted)
            ->whereIn('reservation_id', (clone $reservations)->select('id'))
            ->sum(DB::raw('amount - refunded_amount'));

        return [
            ['label' => 'Séjours effectués', 'value' => (string) (clone $reservations)->where('statut', ReservationStatus::Completed)->count(), 'icon' => 'fa-suitcase-rolling'],
            ['label' => 'Réservations à venir', 'value' => (string) (clone $reservations)->whereIn('statut', [ReservationStatus::Confirmed, ReservationStatus::Pending])->where('check_in', '>=', today())->count(), 'icon' => 'fa-calendar-check'],
            ['label' => 'Montant réglé', 'value' => number_format($paid, 0, ',', ' ').' FCFA', 'icon' => 'fa-wallet'],
            ['label' => 'Avis publiés', 'value' => (string) Review::whereBelongsTo($this->user)->count(), 'icon' => 'fa-star'],
        ];
    }

    /**
     * @return Collection<int, Reservation>
     */
    public function clientReservations(int $limit = 8): Collection
    {
        return Reservation::query()
            ->whereBelongsTo($this->user)
            ->with('property.city')
            ->orderByDesc('check_in')
            ->limit($limit)
            ->get();
    }

    /*
    |--------------------------------------------------------------------------
    | CALCULS
    |--------------------------------------------------------------------------
    */

    private function revenueBetween(Carbon $from, Carbon $to): int
    {
        return (int) $this->acceptedPayments()->whereBetween('paid_at', [$from, $to])->sum(DB::raw('amount - refunded_amount'));
    }

    private function staysStartingIn(Carbon $month): int
    {
        return $this->reservations()
            ->where('statut', '!=', ReservationStatus::Cancelled)
            ->whereBetween('check_in', [$month->copy()->startOfMonth()->toDateString(), $month->copy()->endOfMonth()->toDateString()])
            ->count();
    }

    /**
     * Taux d'occupation du mois : nuits réservées / nuits disponibles (unités actives × jours du mois), en %.
     */
    private function occupancyRate(): float
    {
        $days = $this->monthStart->daysInMonth;
        $monthEnd = $this->monthStart->copy()->addMonth();

        $capacity = (int) Unit::query()
            ->where('statut', ActiveStatus::Active)
            ->whereIn('property_id', $this->properties()->select('id'))
            ->sum('quantity') * $days;

        if ($capacity === 0) {
            return 0.0;
        }

        $booked = $this->reservations()
            ->with('items')
            ->blocking()
            ->overlapping($this->monthStart, $monthEnd)
            ->get()
            ->sum(function (Reservation $reservation) use ($monthEnd): int {
                $from = $reservation->check_in->max($this->monthStart);
                $to = $reservation->check_out->min($monthEnd);
                $nights = (int) max(0, $from->diffInDays($to));

                return $nights * (int) $reservation->items->sum('quantity');
            });

        return round(min(100, $booked / $capacity * 100), 1);
    }

    /**
     * Évolution en % par rapport à la période précédente (null quand elle est vide).
     */
    private static function trend(int $current, int $previous): ?float
    {
        return $previous === 0 ? null : round(($current - $previous) / $previous * 100, 1);
    }
}
