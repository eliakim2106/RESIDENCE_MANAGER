@extends('layouts.admin')

@php
    $money = fn (int $amount): string => number_format($amount, 0, ',', ' ').' FCFA';
@endphp

@section('title', 'Paiements')

@section('content')
    <div class="admin-page-header">
        <div class="admin-page-heading">
            <span class="admin-page-icon"><i class="fa-solid fa-wallet"></i></span>
            <div>
                <h1>Paiements</h1>
                <p>{{ auth()->user()->isAdmin() ? 'Tous les paiements reçus sur la plateforme.' : 'Les paiements reçus pour vos établissements.' }}</p>
            </div>
        </div>

        <div class="admin-page-actions">
            <form method="GET" class="list-filter" aria-label="Filtrer par moyen de paiement">
                @foreach (request()->except(['moyen', 'page']) as $name => $value)
                    @if (is_string($value) && $value !== '')
                        <input type="hidden" name="{{ $name }}" value="{{ $value }}">
                    @endif
                @endforeach
                <i class="fa-solid fa-credit-card"></i>
                <select name="moyen" data-auto-submit aria-label="Moyen de paiement">
                    <option value="">Tous les moyens</option>
                    @foreach ($methods as $value => $label)
                        <option value="{{ $value }}" @selected($method?->value === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <noscript><button type="submit" class="btn-secondary">Filtrer</button></noscript>
            </form>
        </div>
    </div>

    @include('partials.flash')

    {{-- Totaux, calculés avec les filtres actifs (recherche, moyen de paiement) --}}
    <div class="payment-totals">
        <div class="payment-total">
            <span class="payment-total-icon tone-good"><i class="fa-solid fa-sack-dollar"></i></span>
            <div>
                <span>Total encaissé</span>
                <strong>{{ $money($totals['all']) }}</strong>
            </div>
        </div>
        <div class="payment-total">
            <span class="payment-total-icon tone-info"><i class="fa-solid fa-calendar-day"></i></span>
            <div>
                <span>Encaissé en {{ now()->translatedFormat('F') }}</span>
                <strong>{{ $money($totals['month']) }}</strong>
            </div>
        </div>
        <div class="payment-total">
            <span class="payment-total-icon tone-warning"><i class="fa-solid fa-hourglass-half"></i></span>
            <div>
                <span>Paiements en cours</span>
                <strong>{{ $money($totals['pending']) }}</strong>
            </div>
        </div>
    </div>

    @include('admin.partials.list-toolbar', [
        'tabs' => $tabs,
        'counts' => $counts,
        'statut' => $tab,
        'search' => $search,
        'placeholder' => 'Transaction, référence, client…',
    ])

    <div class="table-card">
        <table class="custom-table">
            <thead>
                <tr>
                    <th>Transaction</th>
                    <th>Réservation</th>
                    <th>Moyen</th>
                    <th>Montant</th>
                    <th>Statut</th>
                </tr>
            </thead>

            <tbody>
                @forelse ($payments as $payment)
                    @php
                        $reservation = $payment->reservation;
                    @endphp
                    <tr>
                        <td class="cell-main">
                            <div class="cell-entity">
                                <span class="cell-icon"><i class="fa-solid {{ $payment->method === App\Enums\PaymentMethod::Cash ? 'fa-money-bill-wave' : ($payment->method === App\Enums\PaymentMethod::Card ? 'fa-credit-card' : 'fa-mobile-screen') }}"></i></span>
                                <span class="cell-entity-text">
                                    <strong class="cell-mono">{{ $payment->operator_reference ?: $payment->transaction_id }}</strong>
                                    <small>{{ ($payment->paid_at ?? $payment->created_at)->translatedFormat('d M Y à H:i') }}</small>
                                </span>
                            </div>
                        </td>
                        <td>
                            @if ($reservation)
                                <span class="cell-stack">
                                    <a href="{{ route('admin.reservations.show', $reservation) }}" class="cell-link">{{ $reservation->reference }}</a>
                                    <small>{{ $reservation->guest_name }} · {{ $reservation->property?->name }}</small>
                                </span>
                            @else
                                <span class="cell-muted">—</span>
                            @endif
                        </td>
                        <td>
                            <span class="cell-stack">
                                <span>{{ $payment->method?->label() ?? '—' }}</span>
                                @if ($payment->operator)
                                    <small>{{ $payment->operator }}</small>
                                @endif
                            </span>
                        </td>
                        <td><span class="cell-amount">{{ $money($payment->amount) }}</span></td>
                        <td><span class="status-pill status-{{ $payment->status->tone() }}">{{ $payment->status->label() }}</span></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="empty-table">
                            @include('admin.partials.empty-state', ['icon' => 'fa-wallet', 'search' => $search])
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @include('admin.partials.pagination', ['paginator' => $payments, 'label' => 'paiement(s)'])
@endsection
