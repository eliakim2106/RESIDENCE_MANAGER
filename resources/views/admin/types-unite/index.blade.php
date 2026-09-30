@extends('layouts.admin')

@section('title', "Types d'unités")

@section('content')
    <div class="page-card">
        <div class="page-card-header">
            <div>
                <h2>Types d'unités</h2>
                <p>Gérez les types d'unités (chambres, suites, appartements...)</p>
            </div>

            <a href="{{ route('admin.types-unite.create') }}" class="btn-primary">
                <i class="fa-solid fa-plus"></i>
                Nouveau type
            </a>
        </div>
    </div>

    @include('partials.flash')

    <div class="table-toolbar">
        <form method="GET" class="search-box">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="text" name="search" value="{{ $search }}" placeholder="Rechercher un type d'unité...">
        </form>

        <div class="table-counter">
            <i class="fa-solid fa-layer-group"></i>
            <span>{{ $types->total() }}</span>
            type(s) d'unité(s)
        </div>
    </div>

    <div class="table-card">
        <table class="custom-table">
            <thead>
                <tr>
                    <th>Id</th>
                    <th>Icône</th>
                    <th>Nom</th>
                    <th>Description</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>

            <tbody>
                @forelse ($types as $type)
                    <tr>
                        <td>#{{ $type->id }}</td>
                        <td><i class="fa-solid {{ $type->fa_icon }}"></i></td>
                        <td>{{ $type->name }}</td>
                        <td>{{ $type->description }}</td>
                        <td>
                            @if ($type->is_active)
                                <span class="badge-success">Actif</span>
                            @else
                                <span class="badge-danger">Inactif</span>
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('admin.types-unite.edit', $type) }}" class="action-btn edit" title="Modifier">
                                <i class="fa-solid fa-pen"></i>
                            </a>

                            <button type="button" class="action-btn delete delete-btn" title="Supprimer"
                                data-url="{{ route('admin.types-unite.destroy', $type) }}"
                                data-name="{{ $type->name }}">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="empty-table">Aucun type d'unité trouvé</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @include('admin.partials.pagination', ['paginator' => $types, 'label' => "type(s) d'unité(s)"])
@endsection
