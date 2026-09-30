<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\DashboardStats;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Tableau de bord adapté au rôle : gestionnaire (administrateur ou propriétaire) ou client.
 */
class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();
        $stats = new DashboardStats($user);

        if (! $user->isAdmin() && ! $user->isOwner()) {
            return view('admin.dashboard', [
                'role' => 'client',
                'nextStay' => $stats->nextStay(),
                'summary' => $stats->clientSummary(),
                'reservations' => $stats->clientReservations(),
            ]);
        }

        return view('admin.dashboard', [
            'role' => $user->isAdmin() ? 'admin' : 'owner',
            'kpis' => $stats->kpis(),
            'overview' => $stats->overview(),
            'monthlyRevenue' => $stats->monthlyRevenue(),
            'byStatus' => $stats->reservationsByStatus(),
            'tasks' => $stats->tasks(),
            'arrivals' => $stats->upcomingArrivals(),
            'latestReservations' => $stats->latestReservations(),
            'performance' => $stats->propertyPerformance($user->isAdmin() ? 5 : null),
        ]);
    }
}
