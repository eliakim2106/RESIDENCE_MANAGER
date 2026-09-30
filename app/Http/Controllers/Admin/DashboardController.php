<?php

namespace App\Http\Controllers\Admin;

use App\Enums\TransactionStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Property;
use App\Models\PropertyType;
use App\Models\Reservation;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Indicateurs du back-office : toute la plateforme pour un administrateur, ses établissements pour un propriétaire.
 */
class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();
        $scoped = ! $user->isAdmin();

        $properties = fn (): Builder => Property::query()->when($scoped, fn (Builder $query) => $query->ownedBy($user));
        $reservations = fn (): Builder => Reservation::query()
            ->when($scoped, fn (Builder $query) => $query->whereHas('property', fn (Builder $query) => $query->ownedBy($user)));

        $revenue = Payment::query()
            ->where('status', TransactionStatus::Accepted)
            ->whereBetween('paid_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->whereHas('reservation', fn (Builder $query) => $query->whereIn('id', $reservations()->select('id')))
            ->sum('amount');

        $clients = $scoped
            ? $reservations()->distinct()->count('user_id')
            : User::withRole(UserRole::Client)->count();

        $propertyTypes = PropertyType::query()
            ->withCount(['properties' => fn (Builder $query) => $query->when($scoped, fn (Builder $query) => $query->ownedBy($user))])
            ->orderByDesc('properties_count')
            ->get()
            ->where('properties_count', '>', 0);

        return view('admin.dashboard', [
            'stats' => [
                'properties' => $properties()->count(),
                'units' => Unit::query()->whereIn('property_id', $properties()->select('id'))->sum('quantity'),
                'reservations' => $reservations()->count(),
                'revenue' => (int) $revenue,
                'clients' => $clients,
            ],
            'latestReservations' => $reservations()->with(['property', 'user'])->latest('id')->limit(6)->get(),
            'propertyTypes' => $propertyTypes,
        ]);
    }
}
