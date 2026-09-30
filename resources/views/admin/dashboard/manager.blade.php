{{-- Tableau de bord d'un administrateur (toute la plateforme) ou d'un propriétaire (ses établissements) --}}
@php
    // Montant court : 8,6 M · 897 k · 4 500
    $short = function (int $amount): string {
        return match (true) {
            $amount >= 1_000_000 => str_replace(',0', '', number_format($amount / 1_000_000, 1, ',', ' ')).' M',
            $amount >= 10_000 => number_format($amount / 1_000, 0, ',', ' ').' k',
            default => number_format($amount, 0, ',', ' '),
        };
    };
    $money = fn (int $amount): string => number_format($amount, 0, ',', ' ').' FCFA';

    $previousMonth = Str::ucfirst(now()->subMonth()->translatedFormat('F'));

    // Échelle du graphique : maximum arrondi à une valeur ronde, graduations à 0, 50 % et 100 %
    $maxRevenue = max(1, collect($monthlyRevenue)->max('value'));
    $step = 10 ** floor(log10($maxRevenue));
    $scaleMax = (int) (ceil($maxRevenue / $step) * $step);
    $totalRevenue = collect($monthlyRevenue)->sum('value');

    $totalStatus = max(1, collect($byStatus)->sum('count'));
@endphp

{{-- ========== Indicateurs clés ========== --}}
<div class="kpi-grid">

    <article class="kpi-card">
        <div class="kpi-top">
            <span class="kpi-icon kpi-icon-gold"><i class="fa-solid fa-wallet"></i></span>
            <span class="kpi-label">Revenus du mois</span>
        </div>
        <p class="kpi-value">{{ $short($kpis['revenue']['value']) }} <small>FCFA</small></p>
        @include('admin.dashboard.trend', ['trend' => $kpis['revenue']['trend'], 'previous' => $previousMonth])
    </article>

    <article class="kpi-card">
        <div class="kpi-top">
            <span class="kpi-icon"><i class="fa-solid fa-calendar-check"></i></span>
            <span class="kpi-label">Séjours ce mois</span>
        </div>
        <p class="kpi-value">{{ $kpis['bookings']['value'] }}</p>
        @include('admin.dashboard.trend', ['trend' => $kpis['bookings']['trend'], 'previous' => $previousMonth])
    </article>

    <article class="kpi-card">
        <div class="kpi-top">
            <span class="kpi-icon"><i class="fa-solid fa-bed"></i></span>
            <span class="kpi-label">Taux d’occupation</span>
        </div>
        <p class="kpi-value">{{ number_format($kpis['occupancy'], 1, ',', ' ') }} <small>%</small></p>
        <div class="kpi-meter" role="meter" aria-valuenow="{{ $kpis['occupancy'] }}" aria-valuemin="0" aria-valuemax="100" aria-label="Taux d’occupation du mois">
            <span style="width: {{ max(1, $kpis['occupancy']) }}%"></span>
        </div>
        <p class="kpi-note">Nuits réservées sur les nuits disponibles du mois</p>
    </article>

    <article class="kpi-card">
        <div class="kpi-top">
            <span class="kpi-icon kpi-icon-gold"><i class="fa-solid fa-star"></i></span>
            <span class="kpi-label">Note moyenne</span>
        </div>
        @if ($kpis['rating']['value'] !== null)
            <p class="kpi-value">{{ number_format($kpis['rating']['value'], 1, ',', ' ') }} <small>/ 10</small></p>
            <p class="kpi-note">Sur {{ $kpis['rating']['reviews'] }} avis client{{ $kpis['rating']['reviews'] > 1 ? 's' : '' }}</p>
        @else
            <p class="kpi-value kpi-value-empty">—</p>
            <p class="kpi-note">Pas encore d’avis client</p>
        @endif
    </article>

</div>

{{-- ========== Chiffres de contexte ========== --}}
<div class="overview-strip">
    @foreach ($overview as $item)
        <div class="overview-item">
            <i class="fa-solid {{ $item['icon'] }}"></i>
            <div>
                <strong>{{ number_format($item['value'], 0, ',', ' ') }}</strong>
                <span>{{ $item['label'] }} <small>· {{ $item['hint'] }}</small></span>
            </div>
        </div>
    @endforeach
</div>

<div class="dash-grid">

    {{-- ========== Revenus sur 6 mois ========== --}}
    <section class="dash-card dash-span-2">
        <header class="dash-card-header">
            <div>
                <h2>Revenus encaissés</h2>
                <p>6 derniers mois · {{ $money($totalRevenue) }} au total</p>
            </div>
        </header>

        <div class="rev-chart">
            <div class="rev-chart-scale" aria-hidden="true">
                <span>{{ $short($scaleMax) }}</span>
                <span>{{ $short(intdiv($scaleMax, 2)) }}</span>
                <span>0</span>
            </div>

            <div class="rev-chart-plot">
                <div class="rev-chart-grid" aria-hidden="true"><span></span><span></span><span></span></div>

                <div class="rev-chart-bars">
                    @foreach ($monthlyRevenue as $month)
                        <div class="rev-col {{ $month['current'] ? 'is-current' : '' }}">
                            <div class="rev-bar-area">
                                @if ($month['current'])
                                    <span class="rev-direct-label">{{ $short($month['value']) }}</span>
                                @endif
                                <button type="button" class="rev-bar" style="height: {{ round($month['value'] / $scaleMax * 100, 2) }}%"
                                    aria-label="{{ Str::ucfirst($month['long']) }} : {{ $money($month['value']) }}">
                                    <span class="rev-tip" role="tooltip">
                                        <strong>{{ Str::ucfirst($month['long']) }}</strong>
                                        {{ $money($month['value']) }}
                                    </span>
                                </button>
                            </div>
                            <span class="rev-label">{{ $month['label'] }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Les mêmes données sous forme de tableau, pour les lecteurs d'écran --}}
        <table class="visually-hidden">
            <caption>Revenus encaissés par mois</caption>
            <thead><tr><th>Mois</th><th>Revenus</th></tr></thead>
            <tbody>
                @foreach ($monthlyRevenue as $month)
                    <tr><td>{{ $month['long'] }}</td><td>{{ $money($month['value']) }}</td></tr>
                @endforeach
            </tbody>
        </table>
    </section>

    {{-- ========== À traiter + prochaines arrivées ========== --}}
    <section class="dash-card">
        <header class="dash-card-header">
            <div>
                <h2>À traiter</h2>
                <p>Ce qui attend une action de votre part</p>
            </div>
        </header>

        @if ($tasks === [])
            <div class="dash-all-clear">
                <i class="fa-solid fa-circle-check"></i>
                <div>
                    <strong>Tout est à jour</strong>
                    <span>Aucune tâche en attente.</span>
                </div>
            </div>
        @else
            <ul class="task-list">
                @foreach ($tasks as $task)
                    <li class="task-item task-{{ $task['tone'] }}">
                        <span class="task-icon"><i class="fa-solid {{ $task['icon'] }}"></i></span>
                        <span class="task-label">{{ $task['label'] }}</span>
                        @if ($task['url'])
                            <a href="{{ $task['url'] }}" class="task-count">{{ $task['count'] }} <i class="fa-solid fa-chevron-right"></i></a>
                        @else
                            <span class="task-count">{{ $task['count'] }}</span>
                        @endif
                    </li>
                @endforeach
            </ul>
        @endif

        <h3 class="dash-subtitle">Prochaines arrivées</h3>

        @if ($arrivals->isEmpty())
            <p class="dash-empty-line">Aucune arrivée prévue.</p>
        @else
            <ul class="arrival-list">
                @foreach ($arrivals as $arrival)
                    <li>
                        <span class="arrival-date">
                            <strong>{{ $arrival->check_in->format('d') }}</strong>
                            <small>{{ $arrival->check_in->translatedFormat('M') }}</small>
                        </span>
                        <span class="arrival-body">
                            <a href="{{ route('admin.reservations.show', $arrival) }}" class="arrival-link"><strong>{{ $arrival->user?->name ?? $arrival->guest_name }}</strong></a>
                            <small>{{ $arrival->property?->name }} · {{ $arrival->nights }} nuit{{ $arrival->nights > 1 ? 's' : '' }}</small>
                        </span>
                        @if ($arrival->check_in->isToday())
                            <span class="status-pill status-warning">Aujourd’hui</span>
                        @elseif ($arrival->check_in->isTomorrow())
                            <span class="status-pill status-info">Demain</span>
                        @endif
                    </li>
                @endforeach
            </ul>
        @endif
    </section>

    {{-- ========== Dernières réservations ========== --}}
    <section class="dash-card dash-span-2">
        <header class="dash-card-header">
            <div>
                <h2>Dernières réservations</h2>
                <p>Les 6 réservations les plus récentes</p>
            </div>
            <a href="{{ route('admin.reservations.index') }}" class="dash-link">Voir toutes les réservations <i class="fa-solid fa-arrow-right"></i></a>
        </header>

        @if ($latestReservations->isEmpty())
            <div class="empty-state">
                <i class="fa-regular fa-calendar"></i>
                <strong>Aucune réservation pour le moment</strong>
                <span>Les nouvelles réservations apparaîtront ici.</span>
            </div>
        @else
            <div class="table-card dash-table">
                <table class="custom-table">
                    <thead>
                        <tr>
                            <th>Client</th>
                            <th>Séjour</th>
                            <th>Montant</th>
                            <th>Statut</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($latestReservations as $reservation)
                            <tr>
                                <td class="cell-main">
                                    <span class="cell-entity-text">
                                        <strong>{{ $reservation->user?->name ?? $reservation->guest_name }}</strong>
                                        <small><a href="{{ route('admin.reservations.show', $reservation) }}" class="cell-link">{{ $reservation->reference }}</a> · {{ $reservation->property?->name }}</small>
                                    </span>
                                </td>
                                <td class="text-nowrap">
                                    <span class="cell-stack">
                                        <span>{{ $reservation->check_in->format('d/m') }} → {{ $reservation->check_out->format('d/m/y') }}</span>
                                        <small>{{ $reservation->nights }} nuit{{ $reservation->nights > 1 ? 's' : '' }}</small>
                                    </span>
                                </td>
                                <td class="text-nowrap"><strong>{{ $money($reservation->total_amount) }}</strong></td>
                                <td><span class="status-pill status-{{ $reservation->statut->tone() }}">{{ $reservation->statut->label() }}</span></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>

    {{-- ========== Réservations par statut ========== --}}
    <section class="dash-card">
        <header class="dash-card-header">
            <div>
                <h2>Réservations par statut</h2>
                <p>{{ collect($byStatus)->sum('count') }} réservations au total</p>
            </div>
        </header>

        @if ($byStatus === [])
            <p class="dash-empty-line">Aucune réservation.</p>
        @else
            <ul class="status-breakdown">
                @foreach ($byStatus as $row)
                    <li>
                        <div class="status-breakdown-label">
                            <span class="status-dot status-{{ $row['statut']->tone() }}"></span>
                            <span>{{ $row['statut']->label() }}</span>
                            <strong>{{ $row['count'] }}</strong>
                            <small>{{ round($row['count'] / $totalStatus * 100) }} %</small>
                        </div>
                        <div class="status-breakdown-track">
                            <span class="status-{{ $row['statut']->tone() }}" style="width: {{ round($row['count'] / $totalStatus * 100, 1) }}%"></span>
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>

    {{-- ========== Palmarès des établissements ========== --}}
    <section class="dash-card dash-span-3">
        <header class="dash-card-header">
            <div>
                @if ($role === 'admin')
                    <h2>Établissements les plus performants</h2>
                    <p>Classés par revenus encaissés depuis le début</p>
                @else
                    <h2>Performance de vos établissements</h2>
                    <p>Revenus encaissés et réservations depuis le début</p>
                @endif
            </div>
            <a href="{{ route('admin.etablissements.index') }}" class="dash-link">Voir tous les établissements <i class="fa-solid fa-arrow-right"></i></a>
        </header>

        @php $maxPerformance = max(1, $performance->max('revenue')); @endphp

        @if ($performance->isEmpty())
            <div class="empty-state">
                <i class="fa-solid fa-building"></i>
                <strong>Aucun établissement</strong>
                @can('create', App\Models\Property::class)
                    <a href="{{ route('admin.etablissements.create') }}" class="btn-primary">Ajouter un établissement</a>
                @endcan
            </div>
        @else
            <ol class="ranking">
                @foreach ($performance as $property)
                    <li>
                        <span class="ranking-rank">{{ $loop->iteration }}</span>
                        @if ($property->coverImage)
                            <img src="{{ $property->coverImage->url }}" alt="" class="ranking-thumb" loading="lazy">
                        @else
                            <span class="ranking-thumb ranking-thumb-empty"><i class="fa-solid fa-building"></i></span>
                        @endif
                        <span class="ranking-body">
                            <strong>{{ $property->name }}</strong>
                            <small>
                                {{ $property->city?->name }} · {{ $property->reservations_count }} réservation{{ $property->reservations_count > 1 ? 's' : '' }}
                                @if ($property->reviews_count > 0)
                                    · <i class="fa-solid fa-star"></i> {{ number_format((float) $property->rating_average, 1, ',', ' ') }}
                                @endif
                            </small>
                        </span>
                        <span class="ranking-bar" aria-hidden="true"><span style="width: {{ round($property->revenue / $maxPerformance * 100, 1) }}%"></span></span>
                        <strong class="ranking-value">{{ $money((int) $property->revenue) }}</strong>
                    </li>
                @endforeach
            </ol>
        @endif
    </section>

</div>
