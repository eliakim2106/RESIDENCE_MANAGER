@extends('layouts.admin')

@php
    use App\Services\PropertyListing;

    $isAdmin = auth()->user()->isAdmin();
    $money = fn (int $amount): string => number_format($amount, 0, ',', ' ').' FCFA';
@endphp

@section('title', 'Établissements')

@section('content')
    <div class="admin-page-header">
        <div class="admin-page-heading">
            <span class="admin-page-icon"><i class="fa-solid fa-building"></i></span>
            <div>
                <h1>Établissements</h1>
                <p>{{ $isAdmin ? 'Tous les établissements de la plateforme, leur activité et leur état de publication.' : 'Vos établissements, leur activité et leur état de publication.' }}</p>
            </div>
        </div>

        @can('create', App\Models\Property::class)
            <div class="admin-page-actions">
                <a href="{{ route('admin.etablissements.create') }}" class="btn-primary">
                    <i class="fa-solid fa-plus"></i>
                    Nouvel établissement
                </a>
            </div>
        @endcan
    </div>

    @include('partials.flash')

    {{-- ========== Synthèse ========== --}}
    <div class="resa-today">
        <a href="{{ route('admin.etablissements.index', ['statut' => 'publies']) }}" class="resa-today-card tone-good">
            <span class="resa-today-icon"><i class="fa-solid fa-globe"></i></span>
            <span class="resa-today-text">
                <strong>{{ $summary['published'] }}</strong>
                <span>Publié{{ $summary['published'] > 1 ? 's' : '' }} sur le site</span>
            </span>
        </a>
        <a href="{{ $isAdmin ? route('admin.validations.index') : route('admin.etablissements.index', ['statut' => 'en-attente']) }}"
            class="resa-today-card tone-warning {{ $summary['pending'] > 0 ? 'has-alert' : '' }}">
            <span class="resa-today-icon"><i class="fa-solid fa-hourglass-half"></i></span>
            <span class="resa-today-text">
                <strong>{{ $summary['pending'] }}</strong>
                <span>{{ $isAdmin ? 'À valider' : 'En attente de validation' }}</span>
            </span>
        </a>
        <a href="{{ route('admin.unites.index') }}" class="resa-today-card tone-info">
            <span class="resa-today-icon"><i class="fa-solid fa-door-open"></i></span>
            <span class="resa-today-text">
                <strong>{{ $summary['units'] }}</strong>
                <span>Unité{{ $summary['units'] > 1 ? 's' : '' }} disponible{{ $summary['units'] > 1 ? 's' : '' }} à la réservation</span>
            </span>
        </a>
        <a href="{{ route('admin.paiements.index', ['periode' => 'mois']) }}" class="resa-today-card tone-gold">
            <span class="resa-today-icon"><i class="fa-solid fa-sack-dollar"></i></span>
            <span class="resa-today-text">
                <strong class="is-amount">{{ $money($summary['revenue']) }}</strong>
                <span>Encaissé en {{ now()->translatedFormat('F') }}</span>
            </span>
        </a>
    </div>

    {{-- ========== Filtres ========== --}}
    <section class="resa-filters">
        @include('admin.partials.list-toolbar', [
            'tabs' => PropertyListing::TABS,
            'counts' => $counts,
            'statut' => $listing->tab,
            'search' => $listing->search,
            'placeholder' => $isAdmin ? 'Nom, ville, commune, propriétaire…' : 'Nom, ville, commune, quartier…',
        ])

        <form method="GET" class="resa-filter-row etab-filter-row" aria-label="Filtrer les établissements">
            @foreach (request()->except(['ville', 'type', 'proprietaire', 'tri', 'page']) as $name => $value)
                @if (is_string($value) && $value !== '')
                    <input type="hidden" name="{{ $name }}" value="{{ $value }}">
                @endif
            @endforeach

            @if ($listing->cities()->count() > 1)
                <div class="list-filter">
                    <i class="fa-solid fa-location-dot"></i>
                    <select name="ville" data-auto-submit aria-label="Ville">
                        <option value="">Toutes les villes</option>
                        @foreach ($listing->cities() as $city)
                            <option value="{{ $city->slug }}" @selected($listing->city?->is($city))>{{ $city->name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif

            @if ($listing->types()->count() > 1)
                <div class="list-filter">
                    <i class="fa-solid fa-hotel"></i>
                    <select name="type" data-auto-submit aria-label="Type d’établissement">
                        <option value="">Tous les types</option>
                        @foreach ($listing->types() as $type)
                            <option value="{{ $type->slug }}" @selected($listing->type?->is($type))>{{ $type->name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif

            @if ($listing->owners()->count() > 1)
                <div class="list-filter">
                    <i class="fa-solid fa-user-tie"></i>
                    <select name="proprietaire" data-auto-submit aria-label="Propriétaire">
                        <option value="">Tous les propriétaires</option>
                        @foreach ($listing->owners() as $owner)
                            <option value="{{ $owner->id }}" @selected($listing->owner?->is($owner))>{{ $owner->name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif

            <div class="list-filter list-filter-sort">
                <i class="fa-solid fa-arrow-down-wide-short"></i>
                <select name="tri" data-auto-submit aria-label="Trier par">
                    @foreach (PropertyListing::SORTS as $value => $label)
                        <option value="{{ $value }}" @selected($listing->sort === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </form>

        @if ($listing->hasFilters())
            <div class="active-filters">
                @if ($listing->city)
                    <span class="active-filter"><i class="fa-solid fa-location-dot"></i> {{ $listing->city->name }}</span>
                @endif
                @if ($listing->type)
                    <span class="active-filter"><i class="fa-solid fa-hotel"></i> {{ $listing->type->name }}</span>
                @endif
                @if ($listing->owner)
                    <span class="active-filter"><i class="fa-solid fa-user-tie"></i> {{ $listing->owner->name }}</span>
                @endif
                @if ($listing->search !== '')
                    <span class="active-filter"><i class="fa-solid fa-magnifying-glass"></i> « {{ $listing->search }} »</span>
                @endif
                <a href="{{ route('admin.etablissements.index', array_filter(['statut' => $listing->tab === 'tous' ? null : $listing->tab])) }}" class="active-filters-reset">
                    <i class="fa-solid fa-xmark"></i> Effacer les filtres
                </a>
            </div>
        @endif
    </section>

    {{-- ========== Liste ========== --}}
    <div class="table-card resa-table-card">
        <table class="custom-table resa-table etab-table">
            <thead>
                <tr>
                    <th>Établissement</th>
                    @if ($isAdmin)
                        <th>Propriétaire</th>
                    @endif
                    <th>Localisation</th>
                    <th>Activité</th>
                    <th>Note</th>
                    <th>Statut</th>
                    <th>Action</th>
                </tr>
            </thead>

            <tbody>
                @forelse ($etablissements as $etablissement)
                    @php
                        $status = $etablissement->statut;
                        $locked = $etablissement->upcoming_count > 0;
                    @endphp
                    <tr class="{{ $etablissement->isPending() ? 'is-pending' : '' }}">
                        <td class="cell-main">
                            <div class="cell-entity">
                                @if ($etablissement->coverImage)
                                    <img src="{{ $etablissement->coverImage->url }}" alt="" class="cell-thumb" loading="lazy">
                                @else
                                    <span class="cell-icon"><i class="fa-solid {{ $etablissement->propertyType->fa_icon }}"></i></span>
                                @endif
                                <span class="cell-entity-text">
                                    <a href="{{ route('admin.etablissements.show', $etablissement) }}" class="cell-title-link"><strong>{{ $etablissement->name }}</strong></a>
                                    <small>
                                        {{ $etablissement->propertyType->name }}
                                        @if ($etablissement->star_rating)
                                            · <span class="etab-stars" aria-label="{{ $etablissement->star_rating }} étoiles">{{ str_repeat('★', $etablissement->star_rating) }}</span>
                                        @endif
                                    </small>
                                </span>
                            </div>
                        </td>
                        @if ($isAdmin)
                            <td>
                                <span class="cell-stack">
                                    <span>{{ $etablissement->owner?->name ?? '—' }}</span>
                                    <small>{{ $etablissement->owner?->company_name ?: $etablissement->owner?->email }}</small>
                                </span>
                            </td>
                        @endif
                        <td>
                            <span class="cell-stack">
                                <span>{{ $etablissement->city->name }}</span>
                                <small>{{ collect([$etablissement->district, $etablissement->neighborhood])->filter()->implode(' · ') }}</small>
                            </span>
                        </td>
                        <td>
                            <span class="cell-stack etab-activity">
                                <span>
                                    <a href="{{ route('admin.unites.index', ['search' => $etablissement->name]) }}" class="cell-link">{{ $etablissement->units_count }} unité{{ $etablissement->units_count > 1 ? 's' : '' }}</a>
                                    · <a href="{{ route('admin.reservations.index', ['etablissement' => $etablissement->slug]) }}" class="cell-link-muted">{{ $etablissement->upcoming_count }} à venir</a>
                                </span>
                                <small>{{ $money((int) $etablissement->month_revenue) }} ce mois</small>
                            </span>
                        </td>
                        <td class="text-nowrap">
                            @if ($etablissement->reviews_count > 0)
                                <span class="etab-rating"><i class="fa-solid fa-star"></i> {{ number_format((float) $etablissement->rating_average, 1, ',', ' ') }}</span>
                                <small class="cell-muted">{{ $etablissement->reviews_count }} avis</small>
                            @else
                                <span class="cell-muted">—</span>
                            @endif
                        </td>
                        <td>
                            <span class="status-pill status-{{ $status->tone() }}">{{ $etablissement->wasRejected() ? 'Refusé' : $status->label() }}</span>
                            @if ($etablissement->wasRejected() || $etablissement->isSuspended())
                                <small class="cell-reason" title="{{ $etablissement->moderation_note }}">{{ Str::limit($etablissement->moderation_note, 70) }}</small>
                            @endif
                        </td>
                        <td>
                            <span class="invoice-actions">
                                <a href="{{ route('admin.etablissements.show', $etablissement) }}" class="action-btn view" title="Voir la fiche" aria-label="Voir la fiche de {{ $etablissement->name }}">
                                    <i class="fa-solid fa-eye"></i>
                                </a>
                                <a href="{{ route('admin.etablissements.edit', $etablissement) }}" class="action-btn edit" title="Modifier" aria-label="Modifier {{ $etablissement->name }}">
                                    <i class="fa-solid fa-pen"></i>
                                </a>
                                <a href="{{ route('admin.etablissements.unites.create', $etablissement) }}" class="action-btn add-unit" title="Ajouter une unité" aria-label="Ajouter une unité à {{ $etablissement->name }}">
                                    <i class="fa-solid fa-plus"></i>
                                </a>
                                @if ($locked)
                                    <span class="action-btn delete is-disabled" title="Suppression impossible : {{ $etablissement->upcoming_count }} réservation(s) en attente ou à venir" aria-disabled="true">
                                        <i class="fa-solid fa-trash"></i>
                                    </span>
                                @else
                                    <button type="button" class="action-btn delete delete-btn" title="Supprimer" aria-label="Supprimer {{ $etablissement->name }}"
                                        data-url="{{ route('admin.etablissements.destroy', $etablissement) }}"
                                        data-name="{{ $etablissement->name }}"
                                        data-detail="{{ $etablissement->units_count > 0 ? 'Ses '.$etablissement->units_count.' unité(s) et ses photos seront retirées du site.' : 'Ses photos seront retirées du site.' }}">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                @endif
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ $isAdmin ? 7 : 6 }}" class="empty-table">
                            @include('admin.partials.empty-state', ['icon' => 'fa-building', 'search' => $listing->search])
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @include('admin.partials.pagination', ['paginator' => $etablissements, 'label' => 'établissement(s)'])
@endsection
