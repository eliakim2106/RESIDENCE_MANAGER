@extends('layouts.admin')

@section('title', "Types d'établissement")

@section('content')
    @include('admin.partials.type-index', [
        'routePrefix' => 'admin.types-etablissement',
        'title' => "Types d'établissement",
        'subtitle' => 'Les catégories d’hébergement proposées aux propriétaires : hôtel, résidence, villa…',
        'icon' => 'fa-hotel',
        'newLabel' => 'Nouveau type',
        'usageRelation' => 'properties_count',
        'usageLabel' => 'établissements',
        'usageRoute' => 'admin.etablissements.index',
        'sorts' => App\Http\Controllers\Admin\PropertyTypeController::SORTS,
    ])
@endsection
