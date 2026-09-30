<?php

namespace App\Http\Controllers\Admin\Concerns;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * Onglets « Tous / Actifs / Inactifs » des listes du back-office.
 */
trait FiltersByStatus
{
    public const STATUS_TABS = ['tous' => 'Tous', 'actifs' => 'Actifs', 'inactifs' => 'Inactifs'];

    /**
     * Compte les éléments de chaque onglet, puis restreint la requête à l'onglet demandé (paramètre « statut »).
     *
     * @param  Builder<*>  $query  requête déjà filtrée (recherche, périmètre du propriétaire)
     * @param  Closure(Builder<*>): mixed  $active  condition qui définit un élément actif
     * @return array{0: array<string, int>, 1: string}
     */
    protected function filterByStatus(Request $request, Builder $query, Closure $active): array
    {
        $status = (string) $request->query('statut');
        $status = array_key_exists($status, self::STATUS_TABS) ? $status : 'tous';

        $counts = [
            'tous' => (clone $query)->count(),
            'actifs' => (clone $query)->where($active)->count(),
        ];
        $counts['inactifs'] = $counts['tous'] - $counts['actifs'];

        match ($status) {
            'actifs' => $query->where($active),
            'inactifs' => $query->whereNot($active),
            default => null,
        };

        return [$counts, $status];
    }
}
