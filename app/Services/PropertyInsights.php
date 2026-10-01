<?php

namespace App\Services;

use App\Enums\ActiveStatus;
use App\Enums\ReservationStatus;
use App\Enums\ReviewStatus;
use App\Enums\TransactionStatus;
use App\Models\Payment;
use App\Models\Property;
use App\Models\Reservation;
use App\Models\Review;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Indicateurs d'un établissement pour sa fiche : occupation et revenus du mois, réservations à venir,
 * prochaines arrivées, avis récents, et ce qui empêche sa suppression.
 */
class PropertyInsights
{
    private readonly CarbonImmutable $monthStart;

    public function __construct(private readonly Property $property)
    {
        $this->monthStart = CarbonImmutable::now()->startOfMonth();
    }

    /**
     * @return array{occupancy: float, revenue: int, revenuePrevious: int, revenueTotal: int, upcoming: int, pending: int, inHouse: int, rating: float, reviews: int}
     */
    public function kpis(): array
    {
        $today = CarbonImmutable::today()->toDateString();

        return [
            'occupancy' => $this->occupancyRate(),
            'revenue' => $this->revenueBetween($this->monthStart, $this->monthStart->endOfMonth()),
            'revenuePrevious' => $this->revenueBetween($this->monthStart->subMonth(), $this->monthStart->subMonth()->endOfMonth()),
            'revenueTotal' => (int) $this->payments()->sum(DB::raw('amount - refunded_amount')),
            'upcoming' => $this->property->reservations()->where('statut', ReservationStatus::Confirmed)->whereDate('check_in', '>=', $today)->count(),
            'pending' => $this->property->reservations()->where('statut', ReservationStatus::Pending)->count(),
            'inHouse' => $this->property->reservations()->where('statut', ReservationStatus::Confirmed)->whereDate('check_in', '<=', $today)->whereDate('check_out', '>', $today)->count(),
            'rating' => (float) $this->property->rating_average,
            'reviews' => (int) $this->property->reviews_count,
        ];
    }

    /**
     * Prochaines arrivées (réservations confirmées ou en attente).
     *
     * @return Collection<int, Reservation>
     */
    public function upcomingArrivals(int $limit = 5): Collection
    {
        return $this->property->reservations()
            ->whereIn('statut', [ReservationStatus::Pending, ReservationStatus::Confirmed])
            ->whereDate('check_in', '>=', CarbonImmutable::today()->toDateString())
            ->with('items.unit')
            ->orderBy('check_in')
            ->limit($limit)
            ->get();
    }

    /**
     * @return Collection<int, Review>
     */
    public function latestReviews(int $limit = 3): Collection
    {
        return $this->property->reviews()
            ->where('statut', ReviewStatus::Approved)
            ->with('user')
            ->latest()
            ->limit($limit)
            ->get();
    }

    /**
     * Réservations en attente ou confirmées dont le séjour n'est pas terminé : elles empêchent la suppression.
     */
    public function activeReservations(): int
    {
        return Reservation::query()
            ->whereBelongsTo($this->property)
            ->whereIn('statut', [ReservationStatus::Pending, ReservationStatus::Confirmed])
            ->whereDate('check_out', '>=', CarbonImmutable::today()->toDateString())
            ->count();
    }

    /**
     * Taux d'occupation du mois en cours : nuits réservées / nuits disponibles (unités actives × jours), en %.
     */
    public function occupancyRate(): float
    {
        $monthEnd = $this->monthStart->addMonth();
        $capacity = (int) $this->property->units()->where('statut', ActiveStatus::Active)->sum('quantity') * $this->monthStart->daysInMonth;

        if ($capacity === 0) {
            return 0.0;
        }

        $booked = Reservation::query()
            ->whereBelongsTo($this->property)
            ->blocking()
            ->overlapping(Carbon::instance($this->monthStart), Carbon::instance($monthEnd))
            ->with('items')
            ->get()
            ->sum(function (Reservation $reservation) use ($monthEnd): int {
                $from = $reservation->check_in->max($this->monthStart);
                $to = $reservation->check_out->min($monthEnd);

                return (int) max(0, $from->diffInDays($to)) * (int) $reservation->items->sum('quantity');
            });

        return round(min(100, $booked / $capacity * 100), 1);
    }

    /**
     * @return Builder<Payment>
     */
    private function payments(): Builder
    {
        return Payment::query()
            ->where('statut', TransactionStatus::Accepted)
            ->whereIn('reservation_id', $this->property->reservations()->select('id'));
    }

    private function revenueBetween(CarbonImmutable $from, CarbonImmutable $to): int
    {
        return (int) $this->payments()->whereBetween('paid_at', [$from, $to])->sum(DB::raw('amount - refunded_amount'));
    }
}
