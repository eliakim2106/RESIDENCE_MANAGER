{{--
    Liste d'un référentiel de types (types d'établissement, types d'unité).
    Paramètres :
      $types, $counts, $statut, $search, $sort, $sorts, $summary  (contrôleur)
      $routePrefix   préfixe des routes (admin.types-etablissement)
      $title, $subtitle, $icon, $newLabel
      $usageRelation colonne de comptage (properties_count / units_count)
      $usageLabel    libellé au pluriel (établissements / unités)
      $usageRoute    liste filtrée par type (admin.etablissements.index / admin.unites.index)
--}}
@php
    $usageCount = fn ($type): int => (int) $type->{$usageRelation};
@endphp

<div class="admin-page-header">
    <div class="admin-page-heading">
        <span class="admin-page-icon"><i class="fa-solid {{ $icon }}"></i></span>
        <div>
            <h1>{{ $title }}</h1>
            <p>{{ $subtitle }}</p>
        </div>
    </div>

    <div class="admin-page-actions">
        @include('admin.partials.export-button', ['route' => $routePrefix.'.export'])
        <a href="{{ route($routePrefix.'.create') }}" class="btn-primary">
            <i class="fa-solid fa-plus"></i>
            {{ $newLabel }}
        </a>
    </div>
</div>

@include('partials.flash')

{{-- ========== Synthèse ========== --}}
<div class="resa-today">
    <div class="resa-today-card tone-info">
        <span class="resa-today-icon"><i class="fa-solid fa-layer-group"></i></span>
        <span class="resa-today-text">
            <strong>{{ $summary['total'] }}</strong>
            <span>Type{{ $summary['total'] > 1 ? 's' : '' }} au catalogue</span>
        </span>
    </div>
    <div class="resa-today-card tone-good">
        <span class="resa-today-icon"><i class="fa-solid fa-circle-check"></i></span>
        <span class="resa-today-text">
            <strong>{{ $summary['active'] }}</strong>
            <span>Proposé{{ $summary['active'] > 1 ? 's' : '' }} aux propriétaires</span>
        </span>
    </div>
    <div class="resa-today-card tone-gold">
        <span class="resa-today-icon"><i class="fa-solid fa-chart-simple"></i></span>
        <span class="resa-today-text">
            <strong>{{ $summary['used'] }} / {{ $summary['total'] }}</strong>
            <span>Utilisé{{ $summary['used'] > 1 ? 's' : '' }} par au moins un{{ $usageLabel === 'unités' ? 'e unité' : ' établissement' }}</span>
        </span>
    </div>
    <div class="resa-today-card tone-warning">
        <span class="resa-today-icon"><i class="fa-solid fa-trophy"></i></span>
        <span class="resa-today-text">
            <strong class="is-amount">{{ $summary['top']?->name ?? '—' }}</strong>
            <span>{{ $summary['top'] ? 'Le plus utilisé · '.$usageCount($summary['top']).' '.$usageLabel : 'Aucun type utilisé pour le moment' }}</span>
        </span>
    </div>
</div>

{{-- ========== Filtres ========== --}}
<section class="resa-filters">
    @include('admin.partials.list-toolbar', ['counts' => $counts, 'statut' => $statut, 'search' => $search, 'placeholder' => 'Nom ou description…'])

    <form method="GET" class="resa-filter-row etab-filter-row" aria-label="Trier la liste">
        @foreach (request()->except(['tri', 'page']) as $name => $value)
            @if (is_string($value) && $value !== '')
                <input type="hidden" name="{{ $name }}" value="{{ $value }}">
            @endif
        @endforeach
        <div class="list-filter list-filter-sort">
            <i class="fa-solid fa-arrow-down-wide-short"></i>
            <select name="tri" data-auto-submit aria-label="Trier par">
                @foreach ($sorts as $value => $label)
                    <option value="{{ $value }}" @selected($sort === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
    </form>
</section>

{{-- ========== Liste ========== --}}
<div class="table-card resa-table-card">
    <table class="custom-table resa-table">
        <thead>
            <tr>
                <th>Type</th>
                <th>Utilisation</th>
                <th>Créé le</th>
                <th>Statut</th>
                <th>Action</th>
            </tr>
        </thead>

        <tbody>
            @forelse ($types as $type)
                @php
                    $used = $usageCount($type);
                @endphp
                <tr class="{{ $type->isActive() ? '' : 'is-muted-row' }}">
                    <td class="cell-main">
                        <div class="cell-entity">
                            <span class="cell-icon"><i class="fa-solid {{ $type->fa_icon }}"></i></span>
                            <span class="cell-entity-text">
                                <a href="{{ route($routePrefix.'.edit', $type) }}" class="cell-title-link"><strong>{{ $type->name }}</strong></a>
                                <small class="cell-clamp">{{ $type->description ?: 'Sans description' }}</small>
                            </span>
                        </div>
                    </td>
                    <td class="usage-cell">
                        @if ($used > 0)
                            <a href="{{ route($usageRoute, ['type' => $type->slug]) }}" class="cell-link">{{ $used }} {{ $used > 1 ? $usageLabel : rtrim($usageLabel, 's') }}</a>
                        @else
                            <span class="cell-muted">Inutilisé</span>
                        @endif
                        <span class="usage-bar" aria-hidden="true"><span style="width: {{ round($used / $summary['max'] * 100) }}%"></span></span>
                    </td>
                    <td class="text-nowrap cell-muted">{{ $type->created_at?->format('d/m/Y') }}</td>
                    <td><span class="status-pill status-{{ $type->statut->tone() }}">{{ $type->statut->label() }}</span></td>
                    <td>
                        <span class="invoice-actions">
                            <a href="{{ route($routePrefix.'.edit', $type) }}" class="action-btn edit" title="Modifier" aria-label="Modifier {{ $type->name }}">
                                <i class="fa-solid fa-pen"></i>
                            </a>
                            @if ($used > 0)
                                <span class="action-btn delete is-disabled" title="Suppression impossible : utilisé par {{ $used }} {{ $usageLabel }}. Désactivez-le plutôt." aria-disabled="true">
                                    <i class="fa-solid fa-trash"></i>
                                </span>
                            @else
                                <button type="button" class="action-btn delete delete-btn" title="Supprimer" aria-label="Supprimer {{ $type->name }}"
                                    data-url="{{ route($routePrefix.'.destroy', $type) }}"
                                    data-name="{{ $type->name }}">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            @endif
                        </span>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="empty-table">
                        @include('admin.partials.empty-state', ['icon' => $icon, 'search' => $search])
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@include('admin.partials.pagination', ['paginator' => $types, 'label' => 'type(s)'])
