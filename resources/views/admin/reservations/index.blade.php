@extends('layouts.admin')

@php
    $user = auth()->user();
    $isClient = ! $user->isAdmin() && ! $user->isOwner();
    $money = fn (int $amount): string => number_format($amount, 0, ',', ' ').' FCFA';
@endphp

@section('title', $isClient ? 'Mes réservations' : 'Réservations')

@section('content')
    <div class="admin-page-header">
        <div class="admin-page-heading">
            <span class="admin-page-icon"><i class="fa-solid fa-calendar-check"></i></span>
            <div>
                @if ($isClient)
                    <h1>Mes réservations</h1>
                    <p>Vos séjours passés et à venir.</p>
                @else
                    <h1>Réservations</h1>
                    <p>{{ $user->isAdmin() ? 'Toutes les réservations de la plateforme.' : 'Les réservations de vos établissements.' }}</p>
                @endif
            </div>
        </div>

        <div class="admin-page-actions">
            @if ($isClient)
                <a href="{{ route('residences.index') }}" class="btn-primary">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    Trouver une résidence
                </a>
            @elseif ($properties->count() > 1)
                <form method="GET" class="list-filter" aria-label="Filtrer par établissement">
                    @foreach (request()->except(['etablissement', 'page']) as $name => $value)
                        @if (is_string($value) && $value !== '')
                            <input type="hidden" name="{{ $name }}" value="{{ $value }}">
                        @endif
                    @endforeach
                    <i class="fa-solid fa-building"></i>
                    <select name="etablissement" data-auto-submit aria-label="Établissement">
                        <option value="">Tous les établissements</option>
                        @foreach ($properties as $property)
                            <option value="{{ $property->slug }}" @selected($propertySlug === $property->slug)>{{ $property->name }}</option>
                        @endforeach
                    </select>
                    <noscript><button type="submit" class="btn-secondary">Filtrer</button></noscript>
                </form>
            @endif
        </div>
    </div>

    @include('partials.flash')

    @include('admin.partials.list-toolbar', [
        'tabs' => $tabs,
        'counts' => $counts,
        'statut' => $tab,
        'search' => $search,
        'placeholder' => $isClient ? 'Référence, établissement…' : 'Référence, client, email, établissement…',
    ])

    <div class="table-card">
        <table class="custom-table">
            <thead>
                <tr>
                    <th>Réservation</th>
                    @unless ($isClient)
                        <th>Client</th>
                    @endunless
                    <th>Établissement</th>
                    <th>Séjour</th>
                    <th>Montant</th>
                    <th>Statut</th>
                    <th>Action</th>
                </tr>
            </thead>

            <tbody>
                @forelse ($reservations as $reservation)
                    <tr>
                        <td class="cell-main">
                            <span class="cell-entity-text">
                                <a href="{{ route('admin.reservations.show', $reservation) }}" class="cell-title-link"><strong>{{ $reservation->reference }}</strong></a>
                                <small>Le {{ $reservation->created_at->format('d/m/Y') }}</small>
                            </span>
                        </td>
                        @unless ($isClient)
                            <td>
                                <span class="cell-stack">
                                    <span>{{ $reservation->guest_name }}</span>
                                    <small>{{ $reservation->guest_email }}</small>
                                </span>
                            </td>
                        @endunless
                        <td>{{ $reservation->property?->name ?? '—' }}</td>
                        <td class="text-nowrap">
                            <span class="cell-stack">
                                <span>{{ $reservation->check_in->format('d/m') }} → {{ $reservation->check_out->format('d/m/Y') }}</span>
                                <small>{{ $reservation->nights }} nuit{{ $reservation->nights > 1 ? 's' : '' }} · {{ $reservation->adults + $reservation->children }} pers.</small>
                            </span>
                        </td>
                        <td>
                            <span class="cell-stack">
                                <span class="cell-amount">{{ $money($reservation->total_amount) }}</span>
                                <small class="text-tone-{{ $reservation->payment_state->tone() }}">{{ $reservation->payment_state->label() }}</small>
                            </span>
                        </td>
                        <td><span class="status-pill status-{{ $reservation->statut->tone() }}">{{ $reservation->statut->label() }}</span></td>
                        <td>
                            @if ($reservation->statut === App\Enums\ReservationStatus::Pending && auth()->user()->can('manage', $reservation))
                                <form method="POST" action="{{ route('admin.reservations.confirm', $reservation) }}" class="inline-action"
                                    data-confirm="Valider la réservation {{ $reservation->reference }} ?">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="action-btn add-unit" title="Valider" aria-label="Valider la réservation {{ $reservation->reference }}">
                                        <i class="fa-solid fa-check"></i>
                                    </button>
                                </form>
                            @endif
                            <a href="{{ route('admin.reservations.show', $reservation) }}" class="action-btn edit" title="Voir le détail" aria-label="Voir la réservation {{ $reservation->reference }}">
                                <i class="fa-solid fa-eye"></i>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ $isClient ? 6 : 7 }}" class="empty-table">
                            @include('admin.partials.empty-state', ['icon' => 'fa-calendar-check', 'search' => $search])
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @include('admin.partials.pagination', ['paginator' => $reservations, 'label' => 'réservation(s)'])
@endsection
