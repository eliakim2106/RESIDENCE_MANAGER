<?php

namespace App\Services;

use App\Models\Property;
use App\Models\PropertyLike;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * « J'aime » des établissements, ouverts à tous les visiteurs.
 * - Compte connecté : un j'aime par compte. Pour un client, aimer ajoute aussi l'établissement à ses favoris.
 * - Visiteur sans compte : un j'aime par navigateur, reconnu par un identifiant gardé en cookie.
 * Le nombre de j'aime est recopié dans properties.likes_count.
 */
class PropertyLikes
{
    public const COOKIE = 'rm_visiteur';

    /**
     * Durée du cookie visiteur : deux ans.
     */
    private const COOKIE_MINUTES = 60 * 24 * 730;

    /**
     * Établissements aimés par ce visiteur (compte et navigateur).
     *
     * @return list<int>
     */
    public function likedIds(Request $request): array
    {
        return $this->mine($request)?->pluck('property_id')->unique()->values()->all() ?? [];
    }

    /**
     * Aime l'établissement, ou retire le j'aime s'il y en avait un. Renvoie l'état final.
     */
    public function toggle(Request $request, Property $property): bool
    {
        $user = $request->user();
        $visitor = $user ? null : $this->visitorId($request, create: true);

        $liked = DB::transaction(function () use ($request, $property, $user, $visitor): bool {
            $existing = $this->mine($request)->where('property_id', $property->id);
            $isFavorite = $user?->isClient() && $user->favorites()->whereKey($property->id)->exists();

            if ($isFavorite || $existing->exists()) {
                $existing->delete();
                if ($user?->isClient()) {
                    $user->favorites()->detach($property->id);
                }

                return false;
            }

            try {
                PropertyLike::query()->create(['property_id' => $property->id, 'user_id' => $user?->id, 'visitor_id' => $visitor]);
            } catch (UniqueConstraintViolationException) {
                // Double clic : le j'aime existe déjà
            }

            if ($user?->isClient()) {
                $user->favorites()->syncWithoutDetaching([$property->id]);
            }

            return true;
        });

        $this->refreshCount($property);

        return $liked;
    }

    /**
     * Recompte les j'aime de l'établissement (sans toucher à sa date de modification).
     */
    public function refreshCount(Property $property): void
    {
        $count = $property->likes()->count();

        Property::withTrashed()->whereKey($property->id)->toBase()->update(['likes_count' => $count]);
        $property->forceFill(['likes_count' => $count])->syncOriginalAttribute('likes_count');
    }

    /*
    |--------------------------------------------------------------------------
    | OUTILS
    |--------------------------------------------------------------------------
    */

    /**
     * J'aime de ce visiteur : ceux de son compte et ceux donnés sans compte depuis ce navigateur.
     *
     * @return ?Builder<PropertyLike>
     */
    private function mine(Request $request): ?Builder
    {
        $user = $request->user();
        $visitor = $this->visitorId($request);

        if (! $user && ! $visitor) {
            return null;
        }

        return PropertyLike::query()->where(fn (Builder $query) => $query
            ->when($user, fn (Builder $query) => $query->orWhere('user_id', $user->id))
            ->when($visitor, fn (Builder $query) => $query->orWhere(fn (Builder $query) => $query->whereNull('user_id')->where('visitor_id', $visitor))));
    }

    /**
     * Identifiant du navigateur, lu dans le cookie ; créé au premier j'aime d'un visiteur sans compte.
     */
    private function visitorId(Request $request, bool $create = false): ?string
    {
        $id = $request->cookie(self::COOKIE);

        if (is_string($id) && Str::isUuid($id)) {
            return $id;
        }

        if (! $create) {
            return null;
        }

        $id = (string) Str::uuid();
        Cookie::queue(self::COOKIE, $id, self::COOKIE_MINUTES);
        $request->cookies->set(self::COOKIE, $id);

        return $id;
    }
}
