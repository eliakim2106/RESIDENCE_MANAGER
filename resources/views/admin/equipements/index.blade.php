@extends('layouts.admin')

@section('title', "Équipements")

@section('content')
    <div class="page-card">
        <div class="page-card-header">
            <div>
                <h2>Équipements</h2>
                <p>Gérez les équipements proposés par les établissements et les unités</p>
            </div>

            <a href="{{ route('admin.equipements.create') }}" class="btn-primary">
                <i class="fa-solid fa-plus"></i>
                Nouvel équipement
            </a>
        </div>
    </div>

    @include('partials.flash')

    <div class="table-toolbar">
        <form method="GET" class="search-box">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="text" name="search" value="{{ $search }}" placeholder="Rechercher un équipement...">
        </form>

        <div class="table-counter">
            <i class="fa-solid fa-layer-group"></i>
            <span>{{ $equipements->total() }}</span>
            équipement(s)
        </div>
    </div>

    <div class="table-card">
        <table class="custom-table">
            <thead>
                <tr>
                    <th>Id</th>
                    <th>Icône</th>
                    <th>Nom</th>
                    <th>Catégorie</th>
                    <th>Statut</th>
                    <th>Actions</th>
                </tr>
            </thead>

            <tbody>
                @forelse ($equipements as $equipement)
                    <tr>
                        <td class="cell-muted cell-id">#{{ $equipement->id }}</td>
                        <td><i class="fa-solid {{ $equipement->fa_icon }}"></i></td>
                        <td class="cell-main">{{ $equipement->name }}</td>
                        <td>
                            {{ $equipement->category->label() }}
                            @if ($equipement->is_popular)
                                <i class="fa-solid fa-star text-warning ms-1" title="Populaire"></i>
                            @endif
                        </td>
                        <td>
                            @if ($equipement->is_active)
                                <span class="badge-success">Actif</span>
                            @else
                                <span class="badge-danger">Inactif</span>
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('admin.equipements.edit', $equipement) }}" class="action-btn edit" title="Modifier">
                                <i class="fa-solid fa-pen"></i>
                            </a>

                            <button type="button" class="action-btn delete delete-btn" title="Supprimer"
                                data-url="{{ route('admin.equipements.destroy', $equipement) }}"
                                data-name="{{ $equipement->name }}">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="empty-table">Aucun équipement trouvé</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @include('admin.partials.pagination', ['paginator' => $equipements, 'label' => "équipement(s)"])
@endsection
