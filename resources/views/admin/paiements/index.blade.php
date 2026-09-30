@extends('layouts.admin')

@php
    use App\Enums\PaymentMethod;
    use App\Enums\TransactionStatus;
    use App\Services\PaymentListing;

    $money = fn (int $amount): string => number_format($amount, 0, ',', ' ').' FCFA';

    // Montant court pour les axes : 8,6 M · 897 k · 4 500
    $short = fn (int $amount): string => match (true) {
        $amount >= 1_000_000 => str_replace(',0', '', number_format($amount / 1_000_000, 1, ',', ' ')).' M',
        $amount >= 10_000 => number_format($amount / 1_000, 0, ',', ' ').' k',
        default => number_format($amount, 0, ',', ' '),
    };

    $methodIcon = fn (?PaymentMethod $method): string => match ($method) {
        PaymentMethod::Cash => 'fa-money-bill-wave',
        PaymentMethod::Card => 'fa-credit-card',
        PaymentMethod::Wallet => 'fa-wallet',
        default => 'fa-mobile-screen',
    };

    // Échelle du graphique : maximum arrondi, graduations à 0, 50 % et 100 %
    $points = $timeline['points'];
    $maxValue = max(1, collect($points)->max('value'));
    $step = 10 ** floor(log10($maxValue));
    $scaleMax = (int) (ceil($maxValue / $step) * $step);
    $total = collect($points)->sum('value');
    $dense = count($points) > 14;

    $hasFilters = $listing->period || $listing->property() || $listing->method || $listing->search !== '';
@endphp

@section('title', 'Paiements')

@section('content')
    {{-- ========== En-tête ========== --}}
    <div class="admin-page-header">
        <div class="admin-page-heading">
            <span class="admin-page-icon"><i class="fa-solid fa-wallet"></i></span>
            <div>
                <h1>Paiements</h1>
                <p>{{ auth()->user()->isAdmin() ? 'Tous les paiements reçus sur la plateforme.' : 'Les paiements reçus pour vos établissements.' }}</p>
            </div>
        </div>

        <div class="admin-page-actions">
            <a href="{{ route('admin.paiements.export', request()->except('page')) }}" class="btn-secondary">
                <i class="fa-solid fa-file-arrow-down"></i>
                Exporter
            </a>
        </div>
    </div>

    @include('partials.flash')

    {{-- ========== Synthèse ========== --}}
    <div class="resa-today">
        <div class="resa-today-card tone-good">
            <span class="resa-today-icon"><i class="fa-solid fa-sack-dollar"></i></span>
            <span class="resa-today-text">
                <strong class="is-amount">{{ $money($summary['collected']) }}</strong>
                <span>Encaissé · {{ $summary['collectedCount'] }} paiement{{ $summary['collectedCount'] > 1 ? 's' : '' }}</span>
            </span>
        </div>
        <div class="resa-today-card tone-info">
            <span class="resa-today-icon"><i class="fa-solid fa-scale-balanced"></i></span>
            <span class="resa-today-text">
                <strong class="is-amount">{{ $money($summary['average']) }}</strong>
                <span>Paiement moyen</span>
            </span>
        </div>
        <a href="{{ request()->fullUrlWithQuery(['statut' => 'en-cours', 'page' => null]) }}" class="resa-today-card tone-warning {{ $summary['pendingCount'] > 0 ? 'has-alert' : '' }}">
            <span class="resa-today-icon"><i class="fa-solid fa-hourglass-half"></i></span>
            <span class="resa-today-text">
                <strong class="is-amount">{{ $money($summary['pending']) }}</strong>
                <span>En cours · {{ $summary['pendingCount'] }} paiement{{ $summary['pendingCount'] > 1 ? 's' : '' }}</span>
            </span>
        </a>
        <a href="{{ request()->fullUrlWithQuery(['statut' => 'rembourses', 'page' => null]) }}" class="resa-today-card tone-gold">
            <span class="resa-today-icon"><i class="fa-solid fa-rotate-left"></i></span>
            <span class="resa-today-text">
                <strong class="is-amount">{{ $money($summary['refunded']) }}</strong>
                <span>Remboursé</span>
            </span>
        </a>
    </div>

    {{-- ========== Graphiques ========== --}}
    <div class="pay-charts">
        <section class="dash-card">
            <header class="dash-card-header">
                <div>
                    <h2>Encaissements</h2>
                    <p>
                        {{ $listing->periodLabel() ? Str::ucfirst(Str::after($listing->periodLabel(), 'Paiements ')) : '6 derniers mois' }}
                        · par {{ $timeline['unit'] }} · {{ $money($total) }}
                    </p>
                </div>
            </header>

            <div class="rev-chart {{ $dense ? 'is-dense' : '' }}">
                <div class="rev-chart-scale" aria-hidden="true">
                    <span>{{ $short($scaleMax) }}</span>
                    <span>{{ $short(intdiv($scaleMax, 2)) }}</span>
                    <span>0</span>
                </div>

                <div class="rev-chart-plot">
                    <div class="rev-chart-grid" aria-hidden="true"><span></span><span></span><span></span></div>

                    <div class="rev-chart-bars">
                        @foreach ($points as $index => $point)
                            <div class="rev-col {{ $point['current'] ? 'is-current' : '' }}">
                                <div class="rev-bar-area">
                                    <button type="button" class="rev-bar" style="height: {{ round($point['value'] / $scaleMax * 100, 2) }}%"
                                        aria-label="{{ Str::ucfirst($point['long']) }} : {{ $money($point['value']) }}">
                                        <span class="rev-tip" role="tooltip">
                                            <strong>{{ Str::ucfirst($point['long']) }}</strong>
                                            {{ $money($point['value']) }}
                                        </span>
                                    </button>
                                </div>
                                <span class="rev-label {{ $dense && $index % 5 !== 0 && ! $point['current'] ? 'is-hidden' : '' }}">{{ $point['label'] }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <table class="visually-hidden">
                <caption>Encaissements par {{ $timeline['unit'] }}</caption>
                <thead><tr><th>Période</th><th>Montant</th></tr></thead>
                <tbody>
                    @foreach ($points as $point)
                        <tr><td>{{ $point['long'] }}</td><td>{{ $money($point['value']) }}</td></tr>
                    @endforeach
                </tbody>
            </table>
        </section>

        <section class="dash-card">
            <header class="dash-card-header">
                <div>
                    <h2>Par moyen de paiement</h2>
                    <p>Part des sommes encaissées</p>
                </div>
            </header>

            @if ($byMethod === [])
                <p class="dash-empty-line">Aucun encaissement sur cette sélection.</p>
            @else
                <ul class="method-bars">
                    @foreach ($byMethod as $row)
                        <li>
                            <div class="method-bars-head">
                                <span><i class="fa-solid {{ $methodIcon($row['method']) }}"></i> {{ $row['method']->label() }}</span>
                                <strong>{{ number_format($row['share'], $row['share'] < 10 ? 1 : 0, ',', ' ') }} %</strong>
                            </div>
                            <span class="method-bars-track"><span style="width: {{ $row['share'] }}%"></span></span>
                            <small>{{ $money($row['value']) }} · {{ $row['count'] }} paiement{{ $row['count'] > 1 ? 's' : '' }}</small>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>
    </div>

    {{-- ========== Filtres ========== --}}
    <section class="resa-filters">
        @include('admin.partials.list-toolbar', [
            'tabs' => $tabs,
            'counts' => $counts,
            'statut' => $listing->tab,
            'search' => $listing->search,
            'placeholder' => 'Transaction, référence, client…',
        ])

        <form method="GET" class="resa-filter-row" aria-label="Filtrer les paiements">
            @foreach (request()->except(['periode', 'du', 'au', 'moyen', 'etablissement', 'page']) as $name => $value)
                @if (is_string($value) && $value !== '')
                    <input type="hidden" name="{{ $name }}" value="{{ $value }}">
                @endif
            @endforeach

            <div class="period-chips" role="group" aria-label="Période">
                <span class="period-chips-label"><i class="fa-regular fa-calendar"></i> Période</span>
                <a href="{{ request()->fullUrlWithQuery(['periode' => null, 'du' => null, 'au' => null, 'page' => null]) }}"
                    class="period-chip {{ $listing->period === null ? 'is-active' : '' }}">Toutes</a>
                @foreach (PaymentListing::PERIODS as $key => $label)
                    <a href="{{ request()->fullUrlWithQuery(['periode' => $key, 'du' => null, 'au' => null, 'page' => null]) }}"
                        class="period-chip {{ $listing->period === $key ? 'is-active' : '' }}">{{ $label }}</a>
                @endforeach
            </div>

            <div class="period-range">
                <input type="date" name="du" value="{{ $listing->period === 'personnalisee' ? $listing->from?->toDateString() : '' }}"
                    data-datepicker data-datepicker-start="periode" data-placeholder="Du" aria-label="À partir du">
                <i class="fa-solid fa-arrow-right"></i>
                <input type="date" name="au" value="{{ $listing->period === 'personnalisee' ? $listing->to?->toDateString() : '' }}"
                    data-datepicker data-datepicker-end="periode" data-placeholder="Au" aria-label="Jusqu’au">
                <button type="submit" class="btn-secondary btn-sm">Appliquer</button>
            </div>

            <div class="filter-selects">
                <div class="list-filter">
                    <i class="fa-solid fa-credit-card"></i>
                    <select name="moyen" data-auto-submit aria-label="Moyen de paiement">
                        <option value="">Tous les moyens</option>
                        @foreach ($methods as $value => $label)
                            <option value="{{ $value }}" @selected($listing->method?->value === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                @if ($listing->properties()->count() > 1)
                    <div class="list-filter">
                        <i class="fa-solid fa-building"></i>
                        <select name="etablissement" data-auto-submit aria-label="Établissement">
                            <option value="">Tous les établissements</option>
                            @foreach ($listing->properties() as $property)
                                <option value="{{ $property->slug }}" @selected($listing->property()?->is($property))>{{ $property->name }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
            </div>
        </form>

        @if ($hasFilters)
            <div class="active-filters">
                @if ($listing->periodLabel())
                    <span class="active-filter"><i class="fa-regular fa-calendar"></i> {{ $listing->periodLabel() }}</span>
                @endif
                @if ($listing->method)
                    <span class="active-filter"><i class="fa-solid {{ $methodIcon($listing->method) }}"></i> {{ $listing->method->label() }}</span>
                @endif
                @if ($listing->property())
                    <span class="active-filter"><i class="fa-solid fa-building"></i> {{ $listing->property()->name }}</span>
                @endif
                @if ($listing->search !== '')
                    <span class="active-filter"><i class="fa-solid fa-magnifying-glass"></i> « {{ $listing->search }} »</span>
                @endif
                <a href="{{ route('admin.paiements.index', array_filter(['statut' => $listing->tab === 'tous' ? null : $listing->tab])) }}" class="active-filters-reset">
                    <i class="fa-solid fa-xmark"></i> Effacer les filtres
                </a>
            </div>
        @endif
    </section>

    {{-- ========== Liste ========== --}}
    <div class="table-card resa-table-card">
        <table class="custom-table resa-table">
            <thead>
                <tr>
                    <th>Transaction</th>
                    <th>
                        <a href="{{ $listing->sortUrl('date') }}" class="th-sort {{ $listing->sort === 'date' ? 'is-sorted' : '' }}">
                            Date <i class="fa-solid {{ $listing->sortIcon('date') }}"></i>
                        </a>
                    </th>
                    <th>Réservation</th>
                    <th>Moyen</th>
                    <th>
                        <a href="{{ $listing->sortUrl('montant') }}" class="th-sort {{ $listing->sort === 'montant' ? 'is-sorted' : '' }}">
                            Montant <i class="fa-solid {{ $listing->sortIcon('montant') }}"></i>
                        </a>
                    </th>
                    <th>Statut</th>
                    <th>Action</th>
                </tr>
            </thead>

            <tbody>
                @forelse ($payments as $payment)
                    @php
                        $reservation = $payment->reservation;
                        $date = $payment->paid_at ?? $payment->created_at;
                    @endphp
                    <tr class="{{ $payment->statut === TransactionStatus::Pending ? 'is-pending' : '' }}">
                        <td class="cell-main">
                            <span class="cell-entity-text">
                                <a href="{{ route('admin.paiements.show', $payment) }}" class="resa-ref">{{ Str::limit($payment->transaction_id, 16, '…') }}</a>
                                <small>
                                    @if ($payment->isManual())
                                        <i class="fa-solid fa-hand-holding-dollar"></i> Saisie manuelle
                                    @else
                                        <i class="fa-solid fa-globe"></i> En ligne
                                    @endif
                                    @if ($payment->operator_reference)
                                        · Réf. {{ $payment->operator_reference }}
                                    @endif
                                </small>
                            </span>
                        </td>
                        <td class="text-nowrap">
                            <span class="cell-stack">
                                <span>{{ $date->format('d/m/Y') }}</span>
                                <small>{{ $date->format('H:i') }}</small>
                            </span>
                        </td>
                        <td>
                            @if ($reservation)
                                <span class="cell-stack guest-cell">
                                    <a href="{{ route('admin.reservations.show', $reservation) }}" class="cell-link">{{ $reservation->reference }}</a>
                                    <small title="{{ $reservation->guest_name }} · {{ $reservation->property?->name }}">{{ $reservation->guest_name }} · {{ $reservation->property?->name }}</small>
                                </span>
                            @else
                                <span class="cell-muted">—</span>
                            @endif
                        </td>
                        <td>
                            <div class="cell-entity">
                                <span class="method-icon"><i class="fa-solid {{ $methodIcon($payment->method) }}"></i></span>
                                <span class="cell-stack">
                                    <span class="text-nowrap">{{ $payment->method?->label() ?? '—' }}</span>
                                    @if ($payment->operator)
                                        <small>{{ $payment->operator }}</small>
                                    @endif
                                </span>
                            </div>
                        </td>
                        <td>
                            <span class="cell-stack">
                                <span class="cell-amount">{{ $money($payment->amount) }}</span>
                                @if ($payment->refunded_amount > 0)
                                    <small class="text-tone-info">− {{ $money($payment->refunded_amount) }} remboursé</small>
                                @endif
                            </span>
                        </td>
                        <td>
                            <span class="status-pill status-{{ $payment->statut->tone() }}">{{ $payment->statut->label() }}</span>
                            @if ($payment->isPartiallyRefunded())
                                <small class="cell-flag text-tone-info"><i class="fa-solid fa-rotate-left"></i> Remboursé en partie</small>
                            @endif
                        </td>
                        <td>
                            <div class="row-actions">
                                @if (in_array($payment->statut, [TransactionStatus::Accepted, TransactionStatus::Refunded], true))
                                    <a href="{{ route('admin.paiements.receipt', $payment) }}" class="action-btn" target="_blank" rel="noopener" title="Reçu" aria-label="Reçu du paiement {{ $payment->transaction_id }}">
                                        <i class="fa-solid fa-receipt"></i>
                                    </a>
                                @endif
                                <a href="{{ route('admin.paiements.show', $payment) }}" class="action-btn edit" title="Voir le détail" aria-label="Voir le paiement {{ $payment->transaction_id }}">
                                    <i class="fa-solid fa-eye"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="empty-table">
                            @include('admin.partials.empty-state', ['icon' => 'fa-wallet', 'search' => $listing->search])
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @include('admin.partials.pagination', ['paginator' => $payments, 'label' => 'paiement(s)'])
@endsection
