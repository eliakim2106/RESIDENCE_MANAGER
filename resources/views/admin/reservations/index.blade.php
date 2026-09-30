@extends('layouts.admin')

@php
    use App\Enums\ReservationStatus;
    use App\Services\ReservationListing;

    $user = auth()->user();
    $isManager = $user->isAdmin() || $user->isOwner();
    $money = fn (int $amount): string => number_format($amount, 0, ',', ' ').' FCFA';
    $initials = fn (string $name): string => collect(preg_split('/[\s-]+/', trim($name)))->filter()->take(2)->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))->implode('');
    $hasFilters = $listing->period || $listing->property() || $listing->search !== '';
@endphp

@section('title', $isManager ? 'Réservations' : 'Mes réservations')

@section('content')
    {{-- ========== En-tête ========== --}}
    <div class="admin-page-header">
        <div class="admin-page-heading">
            <span class="admin-page-icon"><i class="fa-solid fa-calendar-check"></i></span>
            <div>
                @if ($isManager)
                    <h1>Réservations</h1>
                    <p>{{ $user->isAdmin() ? 'Toutes les réservations de la plateforme.' : 'Les réservations de vos établissements.' }}</p>
                @else
                    <h1>Mes réservations</h1>
                    <p>Vos séjours passés et à venir.</p>
                @endif
            </div>
        </div>

        <div class="admin-page-actions">
            @if ($isManager)
                <a href="{{ route('admin.reservations.calendar', array_filter(['etablissement' => $listing->property()?->slug])) }}" class="btn-secondary">
                    <i class="fa-regular fa-calendar"></i>
                    Calendrier
                </a>
                <a href="{{ route('admin.reservations.export', request()->except('page')) }}" class="btn-secondary">
                    <i class="fa-solid fa-file-arrow-down"></i>
                    Exporter
                </a>
            @else
                <a href="{{ route('residences.index') }}" class="btn-primary">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    Trouver une résidence
                </a>
            @endif
        </div>
    </div>

    @include('partials.flash')

    {{-- ========== Aujourd'hui ========== --}}
    @if ($isManager)
        <div class="resa-today">
            <a href="{{ route('admin.reservations.index', ['periode' => 'aujourdhui']) }}" class="resa-today-card tone-info">
                <span class="resa-today-icon"><i class="fa-solid fa-plane-arrival"></i></span>
                <span class="resa-today-text">
                    <strong>{{ $today['arrivals'] }}</strong>
                    <span>Arrivée{{ $today['arrivals'] > 1 ? 's' : '' }} aujourd’hui</span>
                </span>
            </a>
            <div class="resa-today-card tone-gold">
                <span class="resa-today-icon"><i class="fa-solid fa-plane-departure"></i></span>
                <span class="resa-today-text">
                    <strong>{{ $today['departures'] }}</strong>
                    <span>Départ{{ $today['departures'] > 1 ? 's' : '' }} aujourd’hui</span>
                </span>
            </div>
            <div class="resa-today-card tone-good">
                <span class="resa-today-icon"><i class="fa-solid fa-bed"></i></span>
                <span class="resa-today-text">
                    <strong>{{ $today['inHouse'] }}</strong>
                    <span>Client{{ $today['inHouse'] > 1 ? 's' : '' }} sur place</span>
                </span>
            </div>
            <a href="{{ route('admin.reservations.index', ['statut' => 'en-attente']) }}" class="resa-today-card tone-warning {{ $today['pending'] > 0 ? 'has-alert' : '' }}">
                <span class="resa-today-icon"><i class="fa-solid fa-hourglass-half"></i></span>
                <span class="resa-today-text">
                    <strong>{{ $today['pending'] }}</strong>
                    <span>À valider</span>
                </span>
                @if ($today['pending'] > 0)
                    <i class="fa-solid fa-arrow-right resa-today-go"></i>
                @endif
            </a>
        </div>
    @endif

    {{-- ========== Filtres ========== --}}
    <section class="resa-filters">
        @include('admin.partials.list-toolbar', [
            'tabs' => $tabs,
            'counts' => $counts,
            'statut' => $listing->tab,
            'search' => $listing->search,
            'placeholder' => $isManager ? 'Référence, client, email, établissement…' : 'Référence, établissement…',
        ])

        <form method="GET" class="resa-filter-row" aria-label="Filtrer par date d’arrivée">
            @foreach (request()->except(['periode', 'du', 'au', 'etablissement', 'page']) as $name => $value)
                @if (is_string($value) && $value !== '')
                    <input type="hidden" name="{{ $name }}" value="{{ $value }}">
                @endif
            @endforeach

            <div class="period-chips" role="group" aria-label="Période d’arrivée">
                <span class="period-chips-label"><i class="fa-regular fa-calendar"></i> Arrivées</span>
                <a href="{{ request()->fullUrlWithQuery(['periode' => null, 'du' => null, 'au' => null, 'page' => null]) }}"
                    class="period-chip {{ $listing->period === null ? 'is-active' : '' }}">Toutes</a>
                @foreach (ReservationListing::PERIODS as $key => $label)
                    <a href="{{ request()->fullUrlWithQuery(['periode' => $key, 'du' => null, 'au' => null, 'page' => null]) }}"
                        class="period-chip {{ $listing->period === $key ? 'is-active' : '' }}">{{ $label }}</a>
                @endforeach
            </div>

            <div class="period-range">
                <input type="date" name="du" value="{{ $listing->period === 'personnalisee' ? $listing->from?->toDateString() : '' }}"
                    data-datepicker data-datepicker-start="periode" data-placeholder="Du" aria-label="Arrivée à partir du">
                <i class="fa-solid fa-arrow-right"></i>
                <input type="date" name="au" value="{{ $listing->period === 'personnalisee' ? $listing->to?->toDateString() : '' }}"
                    data-datepicker data-datepicker-end="periode" data-placeholder="Au" aria-label="Arrivée jusqu’au">
                <button type="submit" class="btn-secondary btn-sm">Appliquer</button>
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
        </form>

        @if ($hasFilters)
            <div class="active-filters">
                @if ($listing->periodLabel())
                    <span class="active-filter"><i class="fa-regular fa-calendar"></i> {{ $listing->periodLabel() }}</span>
                @endif
                @if ($listing->property())
                    <span class="active-filter"><i class="fa-solid fa-building"></i> {{ $listing->property()->name }}</span>
                @endif
                @if ($listing->search !== '')
                    <span class="active-filter"><i class="fa-solid fa-magnifying-glass"></i> « {{ $listing->search }} »</span>
                @endif
                <a href="{{ route('admin.reservations.index', array_filter(['statut' => $listing->tab === 'toutes' ? null : $listing->tab])) }}" class="active-filters-reset">
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
                    <th>
                        <a href="{{ $listing->sortUrl('reservation') }}" class="th-sort {{ $listing->sort === 'reservation' ? 'is-sorted' : '' }}">
                            Réservation <i class="fa-solid {{ $listing->sortIcon('reservation') }}"></i>
                        </a>
                    </th>
                    @if ($isManager)
                        <th>Client</th>
                    @endif
                    <th>Établissement</th>
                    <th>
                        <a href="{{ $listing->sortUrl('sejour') }}" class="th-sort {{ $listing->sort === 'sejour' ? 'is-sorted' : '' }}">
                            Séjour <i class="fa-solid {{ $listing->sortIcon('sejour') }}"></i>
                        </a>
                    </th>
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
                @forelse ($reservations as $reservation)
                    @php
                        $paidRatio = $reservation->total_amount > 0 ? min(100, round($reservation->amount_paid / $reservation->total_amount * 100)) : 0;
                        $units = $reservation->items->map(fn ($item) => $item->unit?->name)->filter()->unique()->implode(', ');
                        $isToday = $reservation->check_in->isToday();
                    @endphp
                    <tr class="{{ $reservation->statut === ReservationStatus::Pending ? 'is-pending' : '' }}">
                        <td class="cell-main">
                            <span class="cell-entity-text">
                                <a href="{{ route('admin.reservations.show', $reservation) }}" class="resa-ref">{{ $reservation->reference }}</a>
                                <small title="Réservée le {{ $reservation->created_at->translatedFormat('d F Y à H:i') }}">Le {{ $reservation->created_at->format('d/m/Y') }}</small>
                            </span>
                        </td>
                        @if ($isManager)
                            <td>
                                <div class="cell-entity">
                                    <span class="guest-avatar" aria-hidden="true">{{ $initials($reservation->guest_name) }}</span>
                                    <span class="cell-entity-text guest-cell">
                                        <strong title="{{ $reservation->guest_name }}">{{ $reservation->guest_name }}</strong>
                                        <small>{{ $reservation->guest_phone ?: $reservation->guest_email }}</small>
                                    </span>
                                </div>
                            </td>
                        @endif
                        <td>
                            <span class="cell-stack property-cell">
                                <span title="{{ $reservation->property?->name }}">{{ $reservation->property?->name ?? '—' }}</span>
                                @if ($units !== '')
                                    <small title="{{ $units }}">{{ $units }}</small>
                                @endif
                            </span>
                        </td>
                        <td>
                            <div class="stay-dates">
                                <span class="stay-date {{ $isToday ? 'is-today' : '' }}">
                                    <strong>{{ $reservation->check_in->format('d') }}</strong>
                                    <small>{{ Str::ucfirst($reservation->check_in->translatedFormat('M')) }}</small>
                                </span>
                                <span class="stay-nights">{{ $reservation->nights }} nuit{{ $reservation->nights > 1 ? 's' : '' }}</span>
                                <span class="stay-date">
                                    <strong>{{ $reservation->check_out->format('d') }}</strong>
                                    <small>{{ Str::ucfirst($reservation->check_out->translatedFormat('M')) }}</small>
                                </span>
                            </div>
                        </td>
                        <td>
                            <div class="amount-cell">
                                <span class="cell-amount">{{ $money($reservation->total_amount) }}</span>
                                <span class="pay-progress" role="img" aria-label="{{ $paidRatio }} % réglé">
                                    <span class="pay-progress-bar tone-{{ $reservation->payment_state->tone() }}" style="width: {{ $paidRatio }}%"></span>
                                </span>
                                <small class="text-tone-{{ $reservation->payment_state->tone() }}">{{ $reservation->payment_state->label() }}</small>
                            </div>
                        </td>
                        <td>
                            <span class="status-pill status-{{ $reservation->statut->tone() }}">{{ $reservation->statut->label() }}</span>
                            @if ($isToday && in_array($reservation->statut, [ReservationStatus::Pending, ReservationStatus::Confirmed], true))
                                <small class="cell-flag"><i class="fa-solid fa-bolt"></i> Arrive aujourd’hui</small>
                            @endif
                        </td>
                        <td>
                            <div class="row-actions">
                                @if ($reservation->statut === ReservationStatus::Pending && $user->can('manage', $reservation))
                                    <form method="POST" action="{{ route('admin.reservations.confirm', $reservation) }}" class="inline-action"
                                        data-confirm="Valider la réservation {{ $reservation->reference }} ?">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="action-btn add-unit" title="Valider" aria-label="Valider la réservation {{ $reservation->reference }}">
                                            <i class="fa-solid fa-check"></i>
                                        </button>
                                    </form>
                                @endif
                                <a href="{{ route('admin.reservations.voucher', $reservation) }}" class="action-btn" target="_blank" rel="noopener" title="Bon de réservation" aria-label="Bon de réservation {{ $reservation->reference }}">
                                    <i class="fa-solid fa-print"></i>
                                </a>
                                <a href="{{ route('admin.reservations.show', $reservation) }}" class="action-btn edit" title="Voir le détail" aria-label="Voir la réservation {{ $reservation->reference }}">
                                    <i class="fa-solid fa-eye"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ $isManager ? 7 : 6 }}" class="empty-table">
                            @include('admin.partials.empty-state', ['icon' => 'fa-calendar-check', 'search' => $listing->search])
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @include('admin.partials.pagination', ['paginator' => $reservations, 'label' => 'réservation(s)'])
@endsection
