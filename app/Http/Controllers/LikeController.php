<?php

namespace App\Http\Controllers;

use App\Models\Property;
use App\Services\PropertyLikes;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Cœur « J'aime » des établissements, sur la fiche et les cartes : ouvert à tous les visiteurs.
 */
class LikeController extends Controller
{
    public function __invoke(Request $request, Property $residence, PropertyLikes $likes): RedirectResponse|JsonResponse
    {
        abort_unless(Property::query()->onSite()->whereKey($residence->id)->exists(), 404);

        $liked = $likes->toggle($request, $residence);
        $count = (int) $residence->likes_count;

        if ($request->expectsJson()) {
            return response()->json(['liked' => $liked, 'count' => $count]);
        }

        $message = match (true) {
            ! $liked => "Vous n’aimez plus « {$residence->name} ».",
            (bool) $request->user()?->isClient() => "Vous aimez « {$residence->name} » : retrouvez-le dans vos favoris.",
            default => "Vous aimez « {$residence->name} ».",
        };

        return back()->with('success', $message);
    }
}
