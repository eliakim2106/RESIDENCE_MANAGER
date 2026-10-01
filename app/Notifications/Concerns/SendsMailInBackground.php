<?php

namespace App\Notifications\Concerns;

use Illuminate\Bus\Queueable;

/**
 * Notification dont l'email part en arrière-plan (file d'attente), à associer à ShouldQueue.
 *
 * - Un serveur d'emails lent ou en panne ne fait plus échouer l'action du visiteur (réservation, paiement…) :
 *   l'envoi est retenté puis consigné dans failed_jobs.
 * - La notification de l'application (canal database) reste enregistrée immédiatement.
 * - L'envoi attend la validation de la transaction en cours (after_commit dans config/queue.php) :
 *   l'email ne part pas pour une opération annulée.
 *
 * En production, un worker doit tourner : php artisan queue:work (voir docs/configuration.md).
 */
trait SendsMailInBackground
{
    use Queueable;

    /** Tentatives d'envoi avant abandon (consigné dans failed_jobs) */
    public int $tries = 3;

    /** Réservation, facture… supprimée entre-temps : l'email est abandonné sans erreur */
    public bool $deleteWhenMissingModels = true;

    /**
     * Délais entre deux tentatives, en secondes.
     *
     * @return list<int>
     */
    public function backoff(): array
    {
        return [60, 300];
    }

    /**
     * Les emails passent par la file d'attente par défaut ; la notification de l'application est enregistrée aussitôt.
     *
     * @return array<string, string>
     */
    public function viaConnections(): array
    {
        return ['database' => 'sync'];
    }
}
