@extends('layouts.admin')

@section('title', 'Tableau de bord')

@section('content')
    <div class="admin-page-header">
        <div class="admin-page-heading">
            <div>
                <h1 class="dash-greeting-title">Bonjour, {{ auth()->user()->name }}</h1>

                @if ($role === 'admin')
                    <p>Voici l’activité de la plateforme ce mois-ci.</p>
                @elseif ($role === 'owner')
                    <p>Voici l’activité de vos établissements ce mois-ci.</p>
                @else
                    <p>Retrouvez vos séjours et vos réservations.</p>
                @endif
            </div>
        </div>

        {{-- Date tout à droite de l'en-tête --}}
        <div class="admin-page-actions">
            @if ($role === 'client')
                <a href="{{ route('residences.index') }}" class="btn-primary">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    Trouver une résidence
                </a>
            @endif

            <span class="date-btn dash-date">
                <i class="fa-regular fa-calendar"></i>
                {{ Str::ucfirst(now()->translatedFormat('l j F Y')) }}
            </span>
        </div>
    </div>

    @include('partials.flash')

    @if ($role === 'client')
        @include('admin.dashboard.client')
    @else
        @include('admin.dashboard.manager')
    @endif
@endsection
