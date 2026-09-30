@extends('layouts.admin')

@section('title', 'Établissements')

@section('content')
    <div class="page-card">
        <div class="page-card-header">
            <div>
                <h2>Établissements</h2>
                <p>Gérez les établissements de votre plateforme</p>
            </div>

            @can('create', App\Models\Property::class)
                <a href="{{ route('admin.etablissements.create') }}" class="btn-primary">
                    <i class="fa-solid fa-plus"></i>
                    Nouvel établissement
                </a>
            @endcan
        </div>
    </div>

    @include('partials.flash')

    <div class="table-toolbar">
        <form method="GET" class="search-box">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="text" name="search" value="{{ $search }}" placeholder="Rechercher un établissement...">
        </form>

        <div class="table-counter">
            <i class="fa-solid fa-layer-group"></i>
            <span>{{ $etablissements->total() }}</span>
            établissement(s)
        </div>
    </div>

    <div class="table-card">
        <table class="custom-table">
            <thead>
                <tr>
                    <th>Id</th>
                    <th>Type</th>
                    <th>Nom</th>
                    <th>Ville</th>
                    <th>Commune</th>
                    <th>Unités</th>
                    <th>Statut</th>
                    <th>Actions</th>
                </tr>
            </thead>

            <tbody>
                @forelse ($etablissements as $etablissement)
                    <tr>
                        <td class="cell-muted cell-id">#{{ $etablissement->id }}</td>
                        <td>{{ $etablissement->propertyType->name }}</td>
                        <td class="cell-main">{{ $etablissement->name }}</td>
                        <td>{{ $etablissement->city->name }}</td>
                        <td>{{ $etablissement->district }}</td>
                        <td>{{ $etablissement->units_count }}</td>
                        <td>
                            @if ($etablissement->isPublished())
                                <span class="badge-success">Actif</span>
                            @else
                                <span class="badge-neutral">{{ $etablissement->status === App\Enums\PropertyStatus::Draft ? 'Brouillon' : $etablissement->status->label() }}</span>
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('admin.etablissements.edit', $etablissement) }}" class="action-btn edit" title="Modifier">
                                <i class="fa-solid fa-pen"></i>
                            </a>

                            <button type="button" class="action-btn delete delete-btn" title="Supprimer"
                                data-url="{{ route('admin.etablissements.destroy', $etablissement) }}"
                                data-name="{{ $etablissement->name }}">
                                <i class="fa-solid fa-trash"></i>
                            </button>

                            <a href="{{ route('admin.etablissements.unites.create', $etablissement) }}" class="action-btn add-unit" title="Ajouter une unité">
                                <i class="fa-solid fa-plus"></i>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="empty-table">Aucun établissement trouvé</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @include('admin.partials.pagination', ['paginator' => $etablissements, 'label' => 'établissement(s)'])
@endsection
