@extends('layouts.admin')

@section('title', "Types d'unité")

@section('content')
    @include('admin.partials.type-index', [
        'routePrefix' => 'admin.types-unite',
        'title' => "Types d'unité",
        'subtitle' => 'Les catégories de chambres et de logements proposées dans les établissements.',
        'icon' => 'fa-bed',
        'newLabel' => 'Nouveau type',
        'usageRelation' => 'units_count',
        'usageLabel' => 'unités',
        'usageRoute' => 'admin.unites.index',
        'sorts' => App\Http\Controllers\Admin\UnitTypeController::SORTS,
    ])
@endsection
