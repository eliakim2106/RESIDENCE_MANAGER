<?php

namespace App\Http\Controllers;

use App\Enums\ActiveStatus;
use App\Enums\ReviewStatus;
use App\Models\City;
use App\Models\Property;
use App\Models\Review;
use App\Models\Unit;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

/**
 * Accueil : résidences mises en avant, chiffres et avis réels de la plateforme.
 */
class HomeController extends Controller
{
    public function __invoke(): View
    {
        $residences = Property::query()->onSite()
            ->with(['city', 'coverImage', 'propertyType', 'equipments' => fn ($query) => $query->where('is_popular', true)->where('statut', ActiveStatus::Active)])
            ->withMin(['units as prix_min' => fn ($query) => $query->where('statut', ActiveStatus::Active)], 'base_price')
            ->withMax(['units as capacite_max' => fn ($query) => $query->where('statut', ActiveStatus::Active)], 'max_adults')
            ->orderByDesc('is_featured')
            ->orderByDesc('rating_average')
            ->orderByDesc('reviews_count')
            ->limit(6)
            ->get();

        // Avis récents et détaillés des voyageurs, en témoignages
        $testimonials = Review::query()
            ->where('statut', ReviewStatus::Approved)
            ->where('rating', '>=', 8)
            ->whereNotNull('comment')
            ->whereHas('property', fn ($query) => $query->onSite())
            ->with(['user', 'property.city'])
            ->latest()
            ->limit(6)
            ->get();

        return view('site.home', [
            'residences' => $residences,
            'testimonials' => $testimonials,
            'stats' => Cache::remember('home.stats', now()->addMinutes(10), fn (): array => $this->stats()),
            'cities' => City::query()
                ->whereHas('properties', fn ($query) => $query->onSite())
                ->withCount(['properties' => fn ($query) => $query->onSite()])
                ->orderByDesc('properties_count')
                ->limit(8)
                ->get(),
        ]);
    }

    /**
     * Chiffres affichés sur l'accueil, tirés de la base.
     *
     * @return array{residences: int, cities: int, units: int, reviews: int, rating: ?float}
     */
    private function stats(): array
    {
        $online = Property::query()->onSite();
        $reviews = Review::query()->where('statut', ReviewStatus::Approved)->whereHas('property', fn ($query) => $query->onSite());

        return [
            'residences' => (clone $online)->count(),
            'cities' => (clone $online)->distinct()->count('city_id'),
            'units' => (int) Unit::query()->where('statut', ActiveStatus::Active)->whereIn('property_id', (clone $online)->select('id'))->sum('quantity'),
            'reviews' => (clone $reviews)->count(),
            'rating' => ($average = (clone $reviews)->avg('rating')) !== null ? round((float) $average, 1) : null,
        ];
    }
}
