@extends('layouts.admin')

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
            <a href="{{ route('admin.equipements.create') }}" class="btn-primary">
                <i class="fa-solid fa-plus"></i>
                Nouvel équipement
            </a>
        </div>
    </div>

    @include('partials.flash')

    @include('admin.partials.list-toolbar', ['counts' => $counts, 'statut' => $statut, 'search' => $search, 'placeholder' => 'Rechercher un équipement…'])

    <div class="table-card">
        <table class="custom-table">
            <thead>
                <tr>
                    <th>Équipement</th>
                    <th>Catégorie</th>
                    <th>Utilisation</th>
                    <th>Statut</th>
                    <th><span class="visually-hidden">Actions</span></th>
                </tr>
            </thead>

            <tbody>
                @forelse ($equipements as $equipement)
                    <tr>
                        <td class="cell-main">
                            <div class="cell-entity">
                                <span class="cell-icon"><i class="fa-solid {{ $equipement->fa_icon }}"></i></span>
                                <span class="cell-entity-text">
                                    <strong>
                                        {{ $equipement->name }}
                                        @if ($equipement->is_popular)
                                            <i class="fa-solid fa-star cell-star" title="Mis en avant"></i>
                                        @endif
                                    </strong>
                                    <small>{{ $equipement->is_popular ? 'Mis en avant sur le site' : 'Équipement standard' }}</small>
                                </span>
                            </div>
                        </td>
                        <td><span class="badge-soft">{{ $equipement->category->label() }}</span></td>
                        <td class="cell-muted">
                            {{ $equipement->properties_count }} établ. · {{ $equipement->units_count }} unité{{ $equipement->units_count > 1 ? 's' : '' }}
                        </td>
                        <td>
                            @if ($equipement->is_active)
                                <span class="badge-success">Actif</span>
                            @else
                                <span class="badge-neutral">Inactif</span>
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('admin.equipements.edit', $equipement) }}" class="action-btn edit" title="Modifier" aria-label="Modifier {{ $equipement->name }}">
                                <i class="fa-solid fa-pen"></i>
                            </a>
                            <button type="button" class="action-btn delete delete-btn" title="Supprimer" aria-label="Supprimer {{ $equipement->name }}"
                                data-url="{{ route('admin.equipements.destroy', $equipement) }}"
                                data-name="{{ $equipement->name }}">
                                <i class="fa-solid fa-trash"></i>
                            </button>
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
