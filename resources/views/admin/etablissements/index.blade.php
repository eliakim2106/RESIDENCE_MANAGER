@extends('layouts.admin')

@section('title', 'Établissements')

@section('content')
    <div class="admin-page-header">
        <div class="admin-page-heading">
            <span class="admin-page-icon"><i class="fa-solid fa-building"></i></span>
            <div>
                <h1>Établissements</h1>
                @if (auth()->user()->isAdmin())
                    <p>Tous les établissements publiés ou en préparation sur la plateforme.</p>
                @else
                    <p>Vos établissements et leur état de publication.</p>
                @endif
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

    @include('admin.partials.list-toolbar', [
        'counts' => $counts,
        'statut' => $statut,
        'search' => $search,
        'placeholder' => 'Nom, ville, commune, quartier…',
        'labels' => ['actifs' => 'Publiés', 'inactifs' => 'Brouillons'],
    ])

    <div class="table-card">
        <table class="custom-table">
            <thead>
                <tr>
                    <th>Établissement</th>
                    <th>Localisation</th>
                    <th>Unités</th>
                    <th>Statut</th>
                    <th><span class="visually-hidden">Actions</span></th>
                </tr>
            </thead>

            <tbody>
                @forelse ($etablissements as $etablissement)
                    <tr>
                        <td class="cell-main">
                            <div class="cell-entity">
                                @if ($etablissement->coverImage)
                                    <img src="{{ $etablissement->coverImage->url }}" alt="" class="cell-thumb" loading="lazy">
                                @else
                                    <span class="cell-icon"><i class="fa-solid {{ $etablissement->propertyType->fa_icon }}"></i></span>
                                @endif
                                <span class="cell-entity-text">
                                    <strong>{{ $etablissement->name }}</strong>
                                    <small>{{ $etablissement->propertyType->name }}</small>
                                </span>
                            </div>
                        </td>
                        <td>
                            <span class="cell-stack">
                                <span>{{ $etablissement->city->name }}</span>
                                <small>{{ collect([$etablissement->district, $etablissement->neighborhood])->filter()->implode(' · ') }}</small>
                            </span>
                        </td>
                        <td>
                            <a href="{{ route('admin.unites.index', ['search' => $etablissement->name]) }}" class="cell-link" title="Voir les unités">
                                {{ $etablissement->units_count }} unité{{ $etablissement->units_count > 1 ? 's' : '' }}
                            </a>
                        </td>
                        <td>
                            @if ($etablissement->isPublished())
                                <span class="badge-success">Publié</span>
                            @else
                                <span class="badge-neutral">{{ $etablissement->status === App\Enums\PropertyStatus::Draft ? 'Brouillon' : $etablissement->status->label() }}</span>
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('admin.etablissements.unites.create', $etablissement) }}" class="action-btn add-unit" title="Ajouter une unité" aria-label="Ajouter une unité à {{ $etablissement->name }}">
                                <i class="fa-solid fa-plus"></i>
                            </a>
                            <a href="{{ route('admin.etablissements.edit', $etablissement) }}" class="action-btn edit" title="Modifier" aria-label="Modifier {{ $etablissement->name }}">
                                <i class="fa-solid fa-pen"></i>
                            </a>
                            <button type="button" class="action-btn delete delete-btn" title="Supprimer" aria-label="Supprimer {{ $etablissement->name }}"
                                data-url="{{ route('admin.etablissements.destroy', $etablissement) }}"
                                data-name="{{ $etablissement->name }}">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="empty-table">
                            @include('admin.partials.empty-state', ['icon' => 'fa-building', 'search' => $search])
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @include('admin.partials.pagination', ['paginator' => $etablissements, 'label' => 'établissement(s)'])
@endsection
