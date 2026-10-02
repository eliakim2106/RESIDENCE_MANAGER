<?php

namespace App\Http\Controllers;

use App\Enums\ActiveStatus;
use App\Enums\ReviewStatus;
use App\Exceptions\WorkflowException;
use App\Models\Property;
use App\Services\BookingEngine;
use App\Services\PropertyLikes;
use App\Services\ResidenceSearch;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

class ResidenceController extends Controller
{
    /**
     * Liste des établissements publiés, avec recherche, filtres et tri.
     */
    public function index(Request $request, PropertyLikes $likes): View
    {
        $search = ResidenceSearch::fromRequest($request);

        $cities = $search->cities();
        $types = $search->propertyTypes();
        $equipments = $search->equipments();

        return view('site.residences', [
            'search' => $search,
            'filters' => $search->filters,
            'residences' => $search->paginate(),
            'cities' => $cities,
            'types' => $types,
            'equipments' => $equipments,
            'priceBounds' => $search->priceBounds(),
            'activeFilters' => $search->activeFilters($cities, $types, $equipments),
            'nights' => $search->nights(),
            'likedIds' => $likes->likedIds($request),
        ]);
    }

    /**
     * Fiche d'une résidence : galerie, logements (prix et disponibilités selon les dates), conditions, carte, avis.
     * Le propriétaire et les administrateurs peuvent aussi voir une fiche pas encore en ligne (aperçu).
     */
    public function show(Request $request, Property $residence, BookingEngine $engine, PropertyLikes $likes): View
    {
        $user = $request->user();
        $online = Property::query()->onSite()->whereKey($residence->id)->exists();
        abort_unless($online || $user?->isAdmin() || $residence->owner_id === $user?->id, 404);

        $residence->load([
            'city', 'propertyType', 'owner',
            // La relation trie déjà par position : reorder() pour mettre la couverture en premier
            'images' => fn ($query) => $query->reorder()->orderByDesc('is_cover')->orderBy('position'),
            'equipments' => fn ($query) => $query->where('statut', ActiveStatus::Active)->orderBy('category')->orderBy('name'),
        ]);

        [$arrival, $departure, $dateError] = $this->dates($request, $engine);
        $adults = max(1, min(30, (int) $request->query('adultes', 2)));
        $children = max(0, min(20, (int) $request->query('enfants', 0)));

        $units = $arrival
            ? $engine->quotes($residence, $arrival, $departure, $adults + $children)
            : $residence->units()->where('statut', ActiveStatus::Active)->with(['unitType', 'images', 'equipments'])->orderByRaw('COALESCE(promo_price, base_price)')->get()
                ->map(fn ($unit) => ['unit' => $unit, 'available' => null, 'nights' => 0, 'subtotal' => 0, 'cleaning' => (int) $unit->cleaning_fee, 'average' => (int) ($unit->promo_price && $unit->promo_price < $unit->base_price ? $unit->promo_price : $unit->base_price), 'issues' => []]);

        $reviews = $residence->reviews()->where('statut', ReviewStatus::Approved)->with('user')->latest()->limit(6)->get();
        $criteria = $residence->reviews()->where('statut', ReviewStatus::Approved)
            ->selectRaw('AVG(cleanliness) as proprete, AVG(comfort) as confort, AVG(location) as emplacement, AVG(staff) as accueil, AVG(value_for_money) as rapport')
            ->first();

        // J'aime du visiteur : cœur de la fiche et des résidences similaires
        $likedIds = $likes->likedIds($request);

        return view('site.residence-details', [
            'residence' => $residence,
            'online' => $online,
            'units' => $units,
            'arrival' => $arrival,
            'departure' => $departure,
            'dateError' => $dateError,
            'adults' => $adults,
            'children' => $children,
            'nights' => $arrival ? (int) $arrival->diffInDays($departure) : null,
            'fromPrice' => (int) ($units->min('average') ?? 0),
            'capacity' => (int) $units->sum(fn ($line) => ($line['unit']->max_adults + $line['unit']->max_children)),
            'reviews' => $reviews,
            'criteria' => $criteria,
            'similar' => Property::query()->onSite()
                ->whereKeyNot($residence->id)
                ->where(fn ($query) => $query->where('city_id', $residence->city_id)->orWhere('property_type_id', $residence->property_type_id))
                ->with(['city', 'coverImage', 'propertyType'])
                ->withMin(['units as prix_min' => fn ($query) => $query->where('statut', ActiveStatus::Active)], 'base_price')
                ->orderByDesc('rating_average')
                ->limit(3)
                ->get(),
            'likedIds' => $likedIds,
            'isLiked' => in_array($residence->id, $likedIds, true),
            'serviceRate' => BookingEngine::serviceFeeRate(),
        ]);
    }

    /**
     * Ancienne adresse de la fiche de démonstration : renvoie vers la liste.
     */
    public function legacy(): RedirectResponse
    {
        return redirect()->route('residences.index');
    }

    /**
     * @return array{0: ?CarbonImmutable, 1: ?CarbonImmutable, 2: ?string}
     */
    private function dates(Request $request, BookingEngine $engine): array
    {
        if (! $request->filled('arrivee') || ! $request->filled('depart')) {
            return [null, null, null];
        }

        try {
            $arrival = CarbonImmutable::parse((string) $request->query('arrivee'))->startOfDay();
            $departure = CarbonImmutable::parse((string) $request->query('depart'))->startOfDay();
            $engine->assertDates($arrival, $departure);
        } catch (WorkflowException $exception) {
            return [null, null, $exception->getMessage()];
        } catch (Throwable) {
            return [null, null, 'Ces dates ne sont pas valides.'];
        }

        return [$arrival, $departure, null];
    }
}
