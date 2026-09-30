<?php

namespace App\Http\Controllers;

use App\Services\ResidenceSearch;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ResidenceController extends Controller
{
    /**
     * Liste des établissements publiés, avec recherche, filtres et tri.
     */
    public function index(Request $request): View
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
        ]);
    }

    public function show(): View
    {
        return view('site.residence-details');
    }
}
