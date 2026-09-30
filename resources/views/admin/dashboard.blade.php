@extends('layouts.admin')

@section('title', 'Tableau de bord')

@php
    $formatAmount = fn (int $amount): string => $amount >= 1_000_000
        ? number_format($amount / 1_000_000, 1, ',', ' ').' M'
        : number_format($amount, 0, ',', ' ');

    $kpis = [
        ['Établissements', number_format($stats['properties'], 0, ',', ' '), 'fa-building', 'blue', 'publiés ou en brouillon'],
        ['Unités', number_format($stats['units'], 0, ',', ' '), 'fa-bed', 'gold', 'chambres et logements'],
        ['Réservations', number_format($stats['reservations'], 0, ',', ' '), 'fa-calendar-check', 'blue', 'au total'],
        ['Revenus', $formatAmount($stats['revenue']), 'fa-wallet', 'gold', 'FCFA encaissés ce mois'],
        ['Clients', number_format($stats['clients'], 0, ',', ' '), 'fa-users', 'blue', auth()->user()->isAdmin() ? 'inscrits' : 'ayant réservé'],
    ];

    $badgeClass = fn (App\Enums\ReservationStatus $status): string => match ($status) {
        App\Enums\ReservationStatus::Confirmed, App\Enums\ReservationStatus::Completed => 'badge-success',
        App\Enums\ReservationStatus::Cancelled => 'badge-danger',
        default => 'badge-neutral',
    };

    $maxType = max(1, $propertyTypes->max('properties_count') ?? 1);
@endphp

@section('content')
    <div class="page-header">
        <div>
            <h1>Tableau de bord</h1>
            <div class="title-line"></div>
        </div>

        <div class="header-actions">
            <span class="date-btn">
                <i class="fa-regular fa-calendar"></i>
                {{ now()->translatedFormat('l j F Y') }}
            </span>
        </div>
    </div>

    @include('partials.flash')

    <div class="stats-grid">
        @foreach ($kpis as [$label, $value, $icon, $color, $hint])
            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-icon {{ $color }}">
                        <i class="fa-solid {{ $icon }}"></i>
                    </div>
                </div>

                <div class="stat-content">
                    <span class="stat-label">{{ $label }}</span>
                    <h2>{{ $value }}</h2>
                    <p>{{ $hint }}</p>
                </div>
            </div>
        @endforeach
    </div>

    <div class="dashboard-grid">

        <section class="analytics-card">
            <div class="analytics-header">
                <h2>Dernières réservations</h2>
            </div>

            @if ($latestReservations->isEmpty())
                <div class="empty-state">
                    <i class="fa-regular fa-calendar"></i>
                    <strong>Aucune réservation pour le moment</strong>
                    <span>Les nouvelles réservations apparaîtront ici.</span>
                </div>
            @else
                <div class="table-card border-0 shadow-none rounded-0">
                    <table class="custom-table">
                        <thead>
                            <tr>
                                <th>Référence</th>
                                <th>Client</th>
                                <th>Séjour</th>
                                <th>Montant</th>
                                <th>Statut</th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach ($latestReservations as $reservation)
                                <tr>
                                    <td class="cell-main text-nowrap">{{ $reservation->reference }}</td>
                                    <td>
                                        <span class="cell-stack">
                                            <span>{{ $reservation->user?->name ?? $reservation->guest_name }}</span>
                                            <small>{{ $reservation->property?->name }}</small>
                                        </span>
                                    </td>
                                    <td class="cell-muted text-nowrap">{{ $reservation->check_in->format('d/m') }} → {{ $reservation->check_out->format('d/m/y') }}</td>
                                    <td class="text-nowrap">{{ number_format($reservation->total_amount, 0, ',', ' ') }} FCFA</td>
                                    <td><span class="{{ $badgeClass($reservation->status) }}">{{ $reservation->status->label() }}</span></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>

        <div class="d-flex flex-column gap-4">

            <section class="analytics-card">
                <div class="analytics-header">
                    <h2>Établissements par type</h2>
                </div>

                <div class="analytics-body">
                    @if ($propertyTypes->isEmpty())
                        <div class="empty-state">
                            <i class="fa-solid fa-building"></i>
                            <strong>Aucun établissement</strong>
                        </div>
                    @else
                        <ul class="bar-list">
                            @foreach ($propertyTypes as $type)
                                <li>
                                    <div class="bar-list-label">
                                        <span>{{ $type->name }}</span>
                                        <strong>{{ $type->properties_count }}</strong>
                                    </div>
                                    <div class="bar-track">
                                        <div class="bar-fill" style="width: {{ round($type->properties_count / $maxType * 100) }}%"></div>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </section>

            <section class="analytics-card">
                <div class="analytics-header">
                    <h2>Accès rapides</h2>
                </div>

                <div class="analytics-body">
                    <div class="quick-actions">
                        @can('create', App\Models\Property::class)
                            <a href="{{ route('admin.etablissements.create') }}" class="quick-action">
                                <i class="fa-solid fa-plus"></i>
                                Nouvel établissement
                            </a>
                        @endcan

                        @if (auth()->user()->isAdmin() || auth()->user()->isOwner())
                            <a href="{{ route('admin.etablissements.index') }}" class="quick-action">
                                <i class="fa-solid fa-building"></i>
                                Établissements
                            </a>

                            <a href="{{ route('admin.unites.index') }}" class="quick-action">
                                <i class="fa-solid fa-door-open"></i>
                                Unités
                            </a>
                        @endif

                        @if (auth()->user()->isAdmin())
                            <a href="{{ route('admin.equipements.index') }}" class="quick-action">
                                <i class="fa-solid fa-wifi"></i>
                                Équipements
                            </a>
                        @endif

                        <a href="{{ route('home') }}" class="quick-action">
                            <i class="fa-solid fa-globe"></i>
                            Voir le site
                        </a>
                    </div>
                </div>
            </section>

        </div>

    </div>
@endsection
