@extends('layouts.admin')

@php
    use App\Enums\ActiveStatus;
    use App\Services\UnitListing;

    $money = fn (int $amount): string => number_format($amount, 0, ',', ' ').' FCFA';
@endphp

@section('title', 'Unités')

@section('content')
    <div class="admin-page-header">
        <div class="admin-page-heading">
            <span class="admin-page-icon"><i class="fa-solid fa-door-open"></i></span>
            <div>
                <h1>Unités</h1>
                <p>Chambres, studios et logements réservables. Une unité s’ajoute depuis la fiche de son établissement.</p>
            </div>
        </div>

        <div class="admin-page-actions">
            @include('admin.partials.export-button', ['route' => 'admin.unites.export'])
            <a href="{{ route('admin.etablissements.index') }}" class="btn-secondary">
                <i class="fa-solid fa-building"></i>
                Établissements
            </a>
        </div>
    </div>

    @include('partials.flash')

    {{-- ========== Synthèse ========== --}}
    <div class="resa-today">
        <div class="resa-today-card tone-good">
            <span class="resa-today-icon"><i class="fa-solid fa-door-open"></i></span>
            <span class="resa-today-text">
                <strong>{{ $summary['units'] }}</strong>
                <span>Unité{{ $summary['units'] > 1 ? 's' : '' }} disponible{{ $summary['units'] > 1 ? 's' : '' }} à la réservation</span>
            </span>
        </div>
        <div class="resa-today-card tone-info">
            <span class="resa-today-icon"><i class="fa-solid fa-user-group"></i></span>
            <span class="resa-today-text">
                <strong>{{ $summary['capacity'] }}</strong>
                <span>Voyageurs accueillis en même temps</span>
            </span>
        </div>
        <div class="resa-today-card tone-gold">
            <span class="resa-today-icon"><i class="fa-solid fa-tag"></i></span>
            <span class="resa-today-text">
                <strong class="is-amount">{{ $money($summary['averagePrice']) }}</strong>
                <span>Prix moyen par nuit</span>
            </span>
        </div>
        <a href="{{ route('admin.reservations.index', ['statut' => 'confirmees']) }}" class="resa-today-card tone-warning">
            <span class="resa-today-icon"><i class="fa-solid fa-calendar-check"></i></span>
            <span class="resa-today-text">
                <strong>{{ $summary['upcoming'] }}</strong>
                <span>Réservation{{ $summary['upcoming'] > 1 ? 's' : '' }} en attente ou à venir</span>
            </span>
        </a>
    </div>

    {{-- ========== Filtres ========== --}}
    <section class="resa-filters">
        @include('admin.partials.list-toolbar', [
            'tabs' => UnitListing::TABS,
            'counts' => $counts,
            'statut' => $listing->tab,
            'search' => $listing->search,
            'placeholder' => 'Unité ou établissement…',
        ])

        <form method="GET" class="resa-filter-row etab-filter-row" aria-label="Filtrer les unités">
            @foreach (request()->except(['etablissement', 'type', 'tri', 'page']) as $name => $value)
                @if (is_string($value) && $value !== '')
                    <input type="hidden" name="{{ $name }}" value="{{ $value }}">
                @endif
            @endforeach

            @if ($listing->properties()->count() > 1)
                <div class="list-filter">
                    <i class="fa-solid fa-building"></i>
                    <select name="etablissement" data-auto-submit aria-label="Établissement">
                        <option value="">Tous les établissements</option>
                        @foreach ($listing->properties() as $property)
                            <option value="{{ $property->slug }}" @selected($listing->property?->is($property))>{{ $property->name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif

            @if ($listing->types()->count() > 1)
                <div class="list-filter">
                    <i class="fa-solid fa-bed"></i>
                    <select name="type" data-auto-submit aria-label="Type d’unité">
                        <option value="">Tous les types</option>
                        @foreach ($listing->types() as $type)
                            <option value="{{ $type->slug }}" @selected($listing->type?->is($type))>{{ $type->name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif

            <div class="list-filter list-filter-sort">
                <i class="fa-solid fa-arrow-down-wide-short"></i>
                <select name="tri" data-auto-submit aria-label="Trier par">
                    @foreach (UnitListing::SORTS as $value => $label)
                        <option value="{{ $value }}" @selected($listing->sort === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </form>

        @if ($listing->hasFilters())
            <div class="active-filters">
                @if ($listing->property)
                    <span class="active-filter"><i class="fa-solid fa-building"></i> {{ $listing->property->name }}</span>
                @endif
                @if ($listing->type)
                    <span class="active-filter"><i class="fa-solid fa-bed"></i> {{ $listing->type->name }}</span>
                @endif
                @if ($listing->search !== '')
                    <span class="active-filter"><i class="fa-solid fa-magnifying-glass"></i> « {{ $listing->search }} »</span>
                @endif
                <a href="{{ route('admin.unites.index', array_filter(['statut' => $listing->tab === 'tous' ? null : $listing->tab])) }}" class="active-filters-reset">
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
                    <th>Unité</th>
                    <th>Établissement</th>
                    <th>Capacité</th>
                    <th>Prix / nuit</th>
                    <th>Statut</th>
                    <th>Action</th>
                </tr>
            </thead>

            <tbody>
                @forelse ($unites as $unite)
                    @php
                        $locked = $unite->upcoming_count > 0;
                    @endphp
                    <tr class="{{ $unite->statut === ActiveStatus::Active ? '' : 'is-muted-row' }}">
                        <td class="cell-main">
                            <div class="cell-entity">
                                @if ($unite->images->first())
                                    <img src="{{ $unite->images->first()->url }}" alt="" class="cell-thumb" loading="lazy">
                                @else
                                    <span class="cell-icon"><i class="fa-solid fa-door-open"></i></span>
                                @endif
                                <span class="cell-entity-text">
                                    <a href="{{ route('admin.unites.edit', $unite) }}" class="cell-title-link"><strong>{{ $unite->name }}</strong></a>
                                    <small>{{ $unite->unitType->name }}{{ $unite->images->count() > 0 ? ' · '.$unite->images->count().' photo'.($unite->images->count() > 1 ? 's' : '') : '' }}</small>
                                </span>
                            </div>
                        </td>
                        <td>
                            <span class="cell-stack">
                                <a href="{{ route('admin.etablissements.show', $unite->property) }}" class="cell-link-muted">{{ $unite->property->name }}</a>
                                <small>{{ $unite->property->city?->name }}</small>
                            </span>
                        </td>
                        <td>
                            <span class="cell-stack">
                                <span class="text-nowrap"><i class="fa-solid fa-user-group cell-inline-icon"></i> {{ $unite->capacity }} pers.</span>
                                <small>
                                    {{ $unite->quantity }} exemplaire{{ $unite->quantity > 1 ? 's' : '' }}
                                    @if ($unite->upcoming_count > 0)
                                        · <a href="{{ route('admin.reservations.index', ['etablissement' => $unite->property->slug]) }}" class="cell-link">{{ $unite->upcoming_count }} à venir</a>
                                    @endif
                                </small>
                            </span>
                        </td>
                        <td class="text-nowrap">
                            @if ($unite->promo_price && $unite->promo_price < $unite->base_price)
                                <span class="cell-stack">
                                    <strong>{{ $money($unite->promo_price) }}</strong>
                                    <small><del>{{ $money($unite->base_price) }}</del> · promo</small>
                                </span>
                            @else
                                <strong>{{ $money($unite->base_price) }}</strong>
                            @endif
                        </td>
                        <td><span class="status-pill status-{{ $unite->statut->tone() }}">{{ $unite->statut === ActiveStatus::Active ? 'Active' : 'Inactive' }}</span></td>
                        <td>
                            <span class="invoice-actions">
                                <a href="{{ route('admin.etablissements.show', $unite->property) }}" class="action-btn view" title="Voir l’établissement" aria-label="Voir l’établissement de {{ $unite->name }}">
                                    <i class="fa-solid fa-eye"></i>
                                </a>
                                <a href="{{ route('admin.unites.edit', $unite) }}" class="action-btn edit" title="Modifier" aria-label="Modifier {{ $unite->name }}">
                                    <i class="fa-solid fa-pen"></i>
                                </a>
                                @if ($locked)
                                    <span class="action-btn delete is-disabled" title="Suppression impossible : {{ $unite->upcoming_count }} réservation(s) en attente ou à venir" aria-disabled="true">
                                        <i class="fa-solid fa-trash"></i>
                                    </span>
                                @else
                                    <button type="button" class="action-btn delete delete-btn" title="Supprimer" aria-label="Supprimer {{ $unite->name }}"
                                        data-url="{{ route('admin.unites.destroy', $unite) }}"
                                        data-name="{{ $unite->name }}"
                                        data-detail="Ses photos et ses tarifs seront retirés.">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                @endif
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="empty-table">
                            @include('admin.partials.empty-state', ['icon' => 'fa-door-open', 'search' => $listing->search])
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @include('admin.partials.pagination', ['paginator' => $unites, 'label' => 'unité(s)'])
@endsection
