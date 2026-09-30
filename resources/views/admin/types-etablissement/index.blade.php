@extends('layouts.admin')

@section('title', "Types d'établissement")

@section('content')
    <div class="admin-page-header">
        <div class="admin-page-heading">
            <span class="admin-page-icon"><i class="fa-solid fa-hotel"></i></span>
            <div>
                <h1>Types d'établissement</h1>
                <p>Les catégories d'hébergement proposées sur la plateforme.</p>
            </div>
        </div>

        <div class="admin-page-actions">
            <a href="{{ route('admin.types-etablissement.create') }}" class="btn-primary">
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
                    <th>Établissements</th>
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
                        <td>{{ $type->properties_count }}</td>
                        <td>
                            @if ($type->isActive())
                                <span class="badge-success">Actif</span>
                            @else
                                <span class="badge-neutral">Inactif</span>
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('admin.types-etablissement.edit', $type) }}" class="action-btn edit" title="Modifier" aria-label="Modifier {{ $type->name }}">
                                <i class="fa-solid fa-pen"></i>
                            </a>
                            <button type="button" class="action-btn delete delete-btn" title="Supprimer" aria-label="Supprimer {{ $type->name }}"
                                data-url="{{ route('admin.types-etablissement.destroy', $type) }}"
                                data-name="{{ $type->name }}">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="empty-table">
                            @include('admin.partials.empty-state', ['icon' => 'fa-hotel', 'search' => $search])
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @include('admin.partials.pagination', ['paginator' => $types, 'label' => "type(s) d'établissement"])
@endsection
