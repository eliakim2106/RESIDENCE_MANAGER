@extends('layouts.admin')

@php
    use App\Http\Controllers\Admin\EquipmentController;

    $usage = fn ($equipment): int => (int) $equipment->units_count + (int) $equipment->properties_count;
@endphp

@section('title', 'Équipements')

@section('content')
    <div class="admin-page-header">
        <div class="admin-page-heading">
            <span class="admin-page-icon"><i class="fa-solid fa-wifi"></i></span>
            <div>
                <h1>Équipements</h1>
                <p>Les services et commodités que les établissements et leurs unités peuvent proposer.</p>
            </div>
        </div>

        <div class="admin-page-actions">
            @include('admin.partials.export-button', ['route' => 'admin.equipements.export'])
            <a href="{{ route('admin.equipements.create') }}" class="btn-primary">
                <i class="fa-solid fa-plus"></i>
                Nouvel équipement
            </a>
        </div>
    </div>

    @include('partials.flash')

    {{-- ========== Synthèse ========== --}}
    <div class="resa-today">
        <div class="resa-today-card tone-info">
            <span class="resa-today-icon"><i class="fa-solid fa-wifi"></i></span>
            <span class="resa-today-text">
                <strong>{{ $summary['active'] }} / {{ $summary['total'] }}</strong>
                <span>Équipements proposés</span>
            </span>
        </div>
        <div class="resa-today-card tone-gold">
            <span class="resa-today-icon"><i class="fa-solid fa-star"></i></span>
            <span class="resa-today-text">
                <strong>{{ $summary['popular'] }}</strong>
                <span>Mis en avant sur le site</span>
            </span>
        </div>
        <div class="resa-today-card tone-good">
            <span class="resa-today-icon"><i class="fa-solid fa-trophy"></i></span>
            <span class="resa-today-text">
                <strong class="is-amount">{{ $summary['top']?->name ?? '—' }}</strong>
                <span>{{ $summary['top'] ? 'Le plus proposé · '.$usage($summary['top']).' fois' : 'Aucun équipement utilisé' }}</span>
            </span>
        </div>
        <div class="resa-today-card tone-warning">
            <span class="resa-today-icon"><i class="fa-solid fa-box-open"></i></span>
            <span class="resa-today-text">
                <strong>{{ $summary['unused'] }}</strong>
                <span>Jamais proposé{{ $summary['unused'] > 1 ? 's' : '' }}</span>
            </span>
        </div>
    </div>

    {{-- ========== Filtres ========== --}}
    <section class="resa-filters">
        @include('admin.partials.list-toolbar', ['counts' => $counts, 'statut' => $statut, 'search' => $search, 'placeholder' => 'Rechercher un équipement…'])

        <form method="GET" class="resa-filter-row" aria-label="Filtrer par catégorie">
            @foreach (request()->except(['categorie', 'tri', 'page']) as $name => $value)
                @if (is_string($value) && $value !== '')
                    <input type="hidden" name="{{ $name }}" value="{{ $value }}">
                @endif
            @endforeach

            <div class="period-chips" role="group" aria-label="Catégorie">
                <span class="period-chips-label"><i class="fa-solid fa-tags"></i> Catégorie</span>
                <a href="{{ request()->fullUrlWithQuery(['categorie' => null, 'page' => null]) }}" class="period-chip {{ $category === null ? 'is-active' : '' }}">Toutes</a>
                @foreach ($categories as $option)
                    @continue(($categoryCounts[$option->value] ?? 0) === 0)
                    <a href="{{ request()->fullUrlWithQuery(['categorie' => $option->value, 'page' => null]) }}" class="period-chip {{ $category === $option ? 'is-active' : '' }}">
                        {{ $option->label() }} <small>{{ $categoryCounts[$option->value] }}</small>
                    </a>
                @endforeach
            </div>

            <div class="list-filter list-filter-sort">
                <i class="fa-solid fa-arrow-down-wide-short"></i>
                <select name="tri" data-auto-submit aria-label="Trier par">
                    @foreach (EquipmentController::SORTS as $value => $label)
                        <option value="{{ $value }}" @selected($sort === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <input type="hidden" name="categorie" value="{{ $category?->value }}">
        </form>
    </section>

    {{-- ========== Liste ========== --}}
    <div class="table-card resa-table-card">
        <table class="custom-table resa-table">
            <thead>
                <tr>
                    <th>Équipement</th>
                    <th>Catégorie</th>
                    <th>Utilisation</th>
                    <th>Statut</th>
                    <th>Action</th>
                </tr>
            </thead>

            <tbody>
                @forelse ($equipements as $equipement)
                    @php
                        $used = $usage($equipement);
                    @endphp
                    <tr class="{{ $equipement->statut->value === 'actif' ? '' : 'is-muted-row' }}">
                        <td class="cell-main">
                            <div class="cell-entity">
                                <span class="cell-icon"><i class="fa-solid {{ $equipement->fa_icon }}"></i></span>
                                <span class="cell-entity-text">
                                    <a href="{{ route('admin.equipements.edit', $equipement) }}" class="cell-title-link">
                                        <strong>{{ $equipement->name }}</strong>
                                        @if ($equipement->is_popular)
                                            <i class="fa-solid fa-star etab-stars" title="Mis en avant sur le site"></i>
                                        @endif
                                    </a>
                                    <small>{{ $equipement->is_popular ? 'Mis en avant sur le site' : 'Équipement standard' }}</small>
                                </span>
                            </div>
                        </td>
                        <td><span class="badge-soft">{{ $equipement->category->label() }}</span></td>
                        <td class="usage-cell">
                            @if ($used > 0)
                                <span>{{ $equipement->properties_count }} établ. · {{ $equipement->units_count }} unité{{ $equipement->units_count > 1 ? 's' : '' }}</span>
                            @else
                                <span class="cell-muted">Jamais proposé</span>
                            @endif
                            <span class="usage-bar" aria-hidden="true"><span style="width: {{ round($used / $summary['max'] * 100) }}%"></span></span>
                        </td>
                        <td><span class="status-pill status-{{ $equipement->statut->tone() }}">{{ $equipement->statut->label() }}</span></td>
                        <td>
                            <span class="invoice-actions">
                                <a href="{{ route('admin.equipements.edit', $equipement) }}" class="action-btn edit" title="Modifier" aria-label="Modifier {{ $equipement->name }}">
                                    <i class="fa-solid fa-pen"></i>
                                </a>
                                @if ($used > 0)
                                    <span class="action-btn delete is-disabled" title="Suppression impossible : proposé {{ $used }} fois. Désactivez-le plutôt." aria-disabled="true">
                                        <i class="fa-solid fa-trash"></i>
                                    </span>
                                @else
                                    <button type="button" class="action-btn delete delete-btn" title="Supprimer" aria-label="Supprimer {{ $equipement->name }}"
                                        data-url="{{ route('admin.equipements.destroy', $equipement) }}"
                                        data-name="{{ $equipement->name }}">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                @endif
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="empty-table">
                            @include('admin.partials.empty-state', ['icon' => 'fa-wifi', 'search' => $search])
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @include('admin.partials.pagination', ['paginator' => $equipements, 'label' => 'équipement(s)'])
@endsection
