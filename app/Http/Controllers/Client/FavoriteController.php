<?php

namespace App\Http\Controllers\Client;

use App\Enums\ActiveStatus;
use App\Http\Controllers\Controller;
use App\Models\Property;
use App\Services\PropertyLikes;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Résidences mises de côté par le client : celles qu'il aime (cœur « J'aime » sur les cartes et la fiche).
 */
class FavoriteController extends Controller
{
    public function index(Request $request): View
    {
        $favorites = $request->user()->favorites()
            ->with(['city', 'coverImage', 'propertyType'])
            ->withMin(['units as prix_min' => fn ($query) => $query->where('statut', ActiveStatus::Active)], 'base_price')
            ->latest('favorites.created_at')
            ->paginate(9);

        return view('client.favorites', ['favorites' => $favorites]);
    }

    /**
     * Ajoute ou retire une résidence des favoris : c'est aussi son j'aime.
     */
    public function toggle(Request $request, Property $residence, PropertyLikes $likes): RedirectResponse|JsonResponse
    {
        $added = $likes->toggle($request, $residence);

        if ($request->expectsJson()) {
            return response()->json(['favorite' => $added]);
        }

        return back()->with('success', $added ? "« {$residence->name} » a été ajouté à vos favoris." : "« {$residence->name} » a été retiré de vos favoris.");
    }
}
