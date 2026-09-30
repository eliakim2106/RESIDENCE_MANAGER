@extends('layouts.admin')

@section('title', 'Unités')

@section('content')
    <div class="admin-page-header">
        <div class="admin-page-heading">
            <span class="admin-page-icon"><i class="fa-solid fa-door-open"></i></span>
            <div>
                <h1>Unités</h1>
                <p>Chambres, studios et logements réservables. Une unité s'ajoute depuis son établissement.</p>
            </div>
        </div>

        <div class="admin-page-actions">
            <a href="{{ route('admin.etablissements.index') }}" class="btn-secondary">
                <i class="fa-solid fa-building"></i>
                Établissements
            </a>
        </div>
    </div>

    @include('partials.flash')

    @include('admin.partials.list-toolbar', ['counts' => $counts, 'statut' => $statut, 'search' => $search, 'placeholder' => 'Unité ou établissement…'])

    <div class="table-card">
        <table class="custom-table">
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
                    <tr>
                        <td class="cell-main">
                            <div class="cell-entity">
                                @if ($unite->images->first())
                                    <img src="{{ $unite->images->first()->url }}" alt="" class="cell-thumb" loading="lazy">
                                @else
                                    <span class="cell-icon"><i class="fa-solid fa-door-open"></i></span>
                                @endif
                                <span class="cell-entity-text">
                                    <strong>{{ $unite->name }}</strong>
                                    <small>{{ $unite->unitType->name }}</small>
                                </span>
                            </div>
                        </td>
                        <td class="cell-muted">{{ $unite->property->name }}</td>
                        <td>
                            <span class="cell-stack">
                                <span><i class="fa-solid fa-user-group cell-inline-icon"></i> {{ $unite->capacity }} pers.</span>
                                <small>{{ $unite->quantity }} unité{{ $unite->quantity > 1 ? 's' : '' }} identique{{ $unite->quantity > 1 ? 's' : '' }}</small>
                            </span>
                        </td>
                        <td class="text-nowrap">
                            @if ($unite->promo_price)
                                <span class="cell-stack">
                                    <strong>{{ number_format($unite->promo_price, 0, ',', ' ') }} FCFA</strong>
                                    <small><del>{{ number_format($unite->base_price, 0, ',', ' ') }}</del> · promo</small>
                                </span>
                            @else
                                <strong>{{ number_format($unite->base_price, 0, ',', ' ') }} FCFA</strong>
                            @endif
                        </td>
                        <td>
                            @if ($unite->statut === App\Enums\ActiveStatus::Active)
                                <span class="badge-success">Active</span>
                            @else
                                <span class="badge-neutral">Inactive</span>
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('admin.unites.edit', $unite) }}" class="action-btn edit" title="Modifier" aria-label="Modifier {{ $unite->name }}">
                                <i class="fa-solid fa-pen"></i>
                            </a>
                            <button type="button" class="action-btn delete delete-btn" title="Supprimer" aria-label="Supprimer {{ $unite->name }}"
                                data-url="{{ route('admin.unites.destroy', $unite) }}"
                                data-name="{{ $unite->name }}">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="empty-table">
                            @include('admin.partials.empty-state', ['icon' => 'fa-door-open', 'search' => $search])
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @include('admin.partials.pagination', ['paginator' => $unites, 'label' => 'unité(s)'])
@endsection
