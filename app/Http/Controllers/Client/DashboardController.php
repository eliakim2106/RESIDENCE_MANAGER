<?php

namespace App\Http\Controllers\Client;

use App\Enums\ReservationStatus;
use App\Http\Controllers\Controller;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Accueil de l'espace client : prochain séjour, réservations à venir, montants à régler, avis à donner.
 */
class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();
        $today = CarbonImmutable::today()->toDateString();

        $upcoming = $user->reservations()
            ->whereIn('statut', [ReservationStatus::Pending, ReservationStatus::Confirmed])
            ->whereDate('check_out', '>=', $today)
            ->with(['property.city', 'property.coverImage', 'items.unit'])
            ->orderBy('check_in')
            ->get();

        $toReview = $user->reservations()
            ->where(fn (Builder $query) => $query->where('statut', ReservationStatus::Completed)
                ->orWhere(fn (Builder $query) => $query->where('statut', ReservationStatus::Confirmed)->whereDate('check_out', '<', $today)))
            ->whereDoesntHave('review')
            ->with('property.coverImage')
            ->latest('check_out')
            ->get();

        $past = $user->reservations()->where('statut', ReservationStatus::Completed);

        return view('client.dashboard', [
            'user' => $user,
            'nextStay' => $upcoming->first(),
            'upcoming' => $upcoming->skip(1)->take(3),
            'toPay' => (int) $upcoming->sum(fn ($reservation) => $reservation->balanceDue()),
            'toPayCount' => $upcoming->filter(fn ($reservation) => $reservation->balanceDue() > 0)->count(),
            'toReview' => $toReview,
            'stats' => [
                'upcoming' => $upcoming->count(),
                'stays' => (clone $past)->count(),
                'nights' => (int) (clone $past)->sum('nights'),
                'favorites' => $user->favorites()->count(),
            ],
            'notifications' => $user->notifications()->latest()->limit(5)->get(),
        ]);
    }
}
