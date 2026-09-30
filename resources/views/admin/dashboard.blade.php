@extends('layouts.admin')

@section('title', 'Tableau de bord')

@section('content')
    <div class="admin-page-header">
        <div class="admin-page-heading">
            <div>
                <h1>Bonjour, {{ auth()->user()->name }}</h1>
                @if ($role === 'admin')
                    <p>Voici l’activité de la plateforme ce mois-ci.</p>
                @elseif ($role === 'owner')
                    <p>Voici l’activité de vos établissements ce mois-ci.</p>
                @else
                    <p>Retrouvez vos séjours et vos réservations.</p>
                @endif
            </div>
        </div>

        <div class="admin-page-actions">
            <span class="date-btn">
                <i class="fa-regular fa-calendar"></i>
                {{ Str::ucfirst(now()->translatedFormat('l j F Y')) }}
            </span>

            @if ($role !== 'client')
                @can('create', App\Models\Property::class)
                    <a href="{{ route('admin.etablissements.create') }}" class="btn-primary">
                        <i class="fa-solid fa-plus"></i>
                        Nouvel établissement
                    </a>
                @endcan
            @else
                <a href="{{ route('residences.index') }}" class="btn-primary">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    Trouver une résidence
                </a>
            @endif
        </div>
    </div>

    @include('partials.flash')

    @if ($role === 'client')
        @include('admin.dashboard.client')
    @else
        @include('admin.dashboard.manager')
    @endif
@endsection
