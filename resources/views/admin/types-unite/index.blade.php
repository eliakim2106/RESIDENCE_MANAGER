@extends('layouts.admin')

@section('title', "Types d'unité")

@section('content')
    <div class="admin-page-header">
        <div class="admin-page-heading">
            <span class="admin-page-icon"><i class="fa-solid fa-bed"></i></span>
            <div>
                <h1>Types d'unité</h1>
                <p>Les catégories de chambres et de logements proposées dans les établissements.</p>
            </div>
        </div>

        <div class="admin-page-actions">
            <a href="{{ route('admin.types-unite.create') }}" class="btn-primary">
                <i class="fa-solid fa-plus"></i>
                Nouveau type
            </a>
        </div>
    </div>

    @include('partials.flash')

    @include('admin.partials.list-toolbar', ['counts' => $counts, 'statut' => $statut, 'search' => $search, 'placeholder' => 'Rechercher un type…'])

    <div class="table-card">
        <table class="custom-table">
            <thead>
                <tr>
                    <th>Type</th>
                    <th>Unités</th>
                    <th>Statut</th>
                    <th>Action</th>
                </tr>
            </thead>

            <tbody>
                @forelse ($types as $type)
                    <tr>
                        <td class="cell-main">
                            <div class="cell-entity">
                                <span class="cell-icon"><i class="fa-solid {{ $type->fa_icon }}"></i></span>
                                <span class="cell-entity-text">
                                    <strong>{{ $type->name }}</strong>
                                    <small class="cell-clamp">{{ $type->description }}</small>
                                </span>
                            </div>
                        </td>
                        <td>{{ $type->units_count }}</td>
                        <td>
                            @if ($type->isActive())
                                <span class="badge-success">Actif</span>
                            @else
                                <span class="badge-neutral">Inactif</span>
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('admin.types-unite.edit', $type) }}" class="action-btn edit" title="Modifier" aria-label="Modifier {{ $type->name }}">
                                <i class="fa-solid fa-pen"></i>
                            </a>
                            <button type="button" class="action-btn delete delete-btn" title="Supprimer" aria-label="Supprimer {{ $type->name }}"
                                data-url="{{ route('admin.types-unite.destroy', $type) }}"
                                data-name="{{ $type->name }}">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="empty-table">
                            @include('admin.partials.empty-state', ['icon' => 'fa-bed', 'search' => $search])
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @include('admin.partials.pagination', ['paginator' => $types, 'label' => "type(s) d'unité"])
@endsection
