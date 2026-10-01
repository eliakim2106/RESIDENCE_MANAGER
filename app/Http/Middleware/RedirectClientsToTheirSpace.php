<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Les clients ont leur espace sur le site (/mon-compte) : une page de l'administration ouverte par un client
 * (ancien lien, notification, favori) le renvoie vers la page équivalente de son espace.
 *
 * Restent ouverts : les documents imprimables (bon de réservation, reçu de paiement) et les notifications.
 */
class RedirectClientsToTheirSpace
{
    /**
     * Pages de l'administration encore utilisées par les clients.
     */
    private const ALLOWED = [
        'admin/reservations/*/bon',
        'admin/paiements/*/recu',
        'admin/notifications',
        'admin/notifications/*',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user?->isClient() || ! $request->isMethod('GET') || ! $request->is('admin', 'admin/*') || $request->is(...self::ALLOWED)) {
            return $next($request);
        }

        // Fiche d'une réservation : même réservation dans l'espace client
        if (preg_match('#^admin/reservations/([A-Za-z0-9-]+)$#', $request->path(), $matches) && $matches[1] !== 'export' && $matches[1] !== 'calendrier') {
            return redirect()->route('client.reservations.show', $matches[1]);
        }

        return redirect()->route(match (true) {
            $request->is('admin/reservations*') => 'client.reservations.index',
            $request->is('admin/profil*') => 'client.profile.edit',
            default => 'client.dashboard',
        });
    }
}
