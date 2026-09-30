@extends('layouts.admin')

@section('title', 'Unités')

@section('content')
    <div class="page-card">
        <div class="page-card-header">
            <div>
                <h2>Unités</h2>
                <p>Chambres, suites, studios et appartements de vos établissements. Pour ajouter une unité, utilisez le bouton <i class="fa-solid fa-plus"></i> d'un établissement.</p>
            </div>

            <a href="{{ route('admin.etablissements.index') }}" class="btn-primary">
                <i class="fa-solid fa-building"></i>
                Établissements
            </a>
        </div>
    </div>

    @include('partials.flash')

    <div class="table-toolbar">
        <form method="GET" class="search-box">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="text" name="search" value="{{ $search }}" placeholder="Rechercher une unité ou un établissement...">
        </form>

        <div class="table-counter">
            <i class="fa-solid fa-layer-group"></i>
            <span>{{ $unites->total() }}</span>
            unité(s)
        </div>
    </div>

    <div class="table-card">
        <table class="custom-table">
            <thead>
                <tr>
                    <th>Id</th>
                    <th>Établissement</th>
                    <th>Type</th>
                    <th>Nom</th>
                    <th>Quantité</th>
                    <th>Prix / nuit</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>

            <tbody>
                @forelse ($unites as $unite)
                    <tr>
                        <td>#{{ $unite->id }}</td>
                        <td>{{ $unite->property->name }}</td>
                        <td>{{ $unite->unitType->name }}</td>
                        <td>{{ $unite->name }}</td>
                        <td>{{ $unite->quantity }}</td>
                        <td>
                            @if ($unite->promo_price)
                                <del class="text-muted">{{ number_format($unite->base_price, 0, ',', ' ') }}</del>
                                {{ number_format($unite->promo_price, 0, ',', ' ') }} FCFA
                            @else
                                {{ number_format($unite->base_price, 0, ',', ' ') }} FCFA
                            @endif
                        </td>
                        <td>
                            @if ($unite->status === App\Enums\UnitStatus::Active)
                                <span class="badge-success">Actif</span>
                            @else
                                <span class="badge-danger">Inactif</span>
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('admin.unites.edit', $unite) }}" class="action-btn edit" title="Modifier">
                                <i class="fa-solid fa-pen"></i>
                            </a>

                            <button type="button" class="action-btn delete delete-btn" title="Supprimer"
                                data-url="{{ route('admin.unites.destroy', $unite) }}"
                                data-name="{{ $unite->name }}">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="empty-table">Aucune unité trouvée</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @include('admin.partials.pagination', ['paginator' => $unites, 'label' => 'unité(s)'])
@endsection
