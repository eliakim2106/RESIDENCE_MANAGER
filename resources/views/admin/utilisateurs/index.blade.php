@extends('layouts.admin')

@php
    use App\Http\Controllers\Admin\UserController;
@endphp

@section('title', 'Utilisateurs')

@section('content')
    <div class="admin-page-header">
        <div class="admin-page-heading">
            <span class="admin-page-icon"><i class="fa-solid fa-users"></i></span>
            <div>
                <h1>Utilisateurs</h1>
                <p>Clients, propriétaires et administrateurs de la plateforme.</p>
            </div>
        </div>

        <div class="admin-page-actions">
            @include('admin.partials.export-button', ['route' => 'admin.utilisateurs.export'])
            <a href="{{ route('admin.utilisateurs.connexions') }}" class="btn-secondary">
                <i class="fa-solid fa-shield-halved"></i>
                Journal des connexions
            </a>
        </div>
    </div>

    @include('partials.flash')

    {{-- ========== Synthèse ========== --}}
    <div class="resa-today">
        <a href="{{ route('admin.utilisateurs.index', ['statut' => 'clients']) }}" class="resa-today-card tone-info">
            <span class="resa-today-icon"><i class="fa-solid fa-user"></i></span>
            <span class="resa-today-text">
                <strong>{{ $summary['clients'] }}</strong>
                <span>Client{{ $summary['clients'] > 1 ? 's' : '' }}</span>
            </span>
        </a>
        <a href="{{ route('admin.utilisateurs.index', ['statut' => 'proprietaires']) }}" class="resa-today-card tone-gold">
            <span class="resa-today-icon"><i class="fa-solid fa-user-tie"></i></span>
            <span class="resa-today-text">
                <strong>{{ $summary['owners'] }}</strong>
                <span>Propriétaire{{ $summary['owners'] > 1 ? 's' : '' }}</span>
            </span>
        </a>
        <a href="{{ route('admin.utilisateurs.index', ['tri' => 'recents']) }}" class="resa-today-card tone-good">
            <span class="resa-today-icon"><i class="fa-solid fa-user-plus"></i></span>
            <span class="resa-today-text">
                <strong>{{ $summary['newThisMonth'] }}</strong>
                <span>Inscrit{{ $summary['newThisMonth'] > 1 ? 's' : '' }} en {{ now()->translatedFormat('F') }}</span>
            </span>
        </a>
        <a href="{{ route('admin.utilisateurs.index', ['etat' => 'non-confirmes']) }}" class="resa-today-card tone-warning {{ $summary['toWatch'] > 0 ? 'has-alert' : '' }}">
            <span class="resa-today-icon"><i class="fa-solid fa-user-clock"></i></span>
            <span class="resa-today-text">
                <strong>{{ $summary['toWatch'] }}</strong>
                <span title="Email non confirmé, compte suspendu ou en attente">Compte{{ $summary['toWatch'] > 1 ? 's' : '' }} à surveiller</span>
            </span>
        </a>
    </div>

    {{-- ========== Filtres ========== --}}
    <section class="resa-filters">
        @include('admin.partials.list-toolbar', [
            'tabs' => $tabs,
            'counts' => $counts,
            'statut' => $tab,
            'search' => $search,
            'placeholder' => 'Nom, email, téléphone, entreprise…',
        ])

        <form method="GET" class="resa-filter-row etab-filter-row" aria-label="Filtrer les comptes">
            @foreach (request()->except(['etat', 'tri', 'page']) as $name => $value)
                @if (is_string($value) && $value !== '')
                    <input type="hidden" name="{{ $name }}" value="{{ $value }}">
                @endif
            @endforeach

            <div class="list-filter">
                <i class="fa-solid fa-user-check"></i>
                <select name="etat" data-auto-submit aria-label="État du compte">
                    <option value="">Tous les états</option>
                    <option value="actifs" @selected($state === 'actifs')>Actifs</option>
                    <option value="en-attente" @selected($state === 'en-attente')>En attente</option>
                    <option value="suspendus" @selected($state === 'suspendus')>Suspendus</option>
                    <option value="non-confirmes" @selected($state === 'non-confirmes')>Email non confirmé</option>
                </select>
            </div>

            <div class="list-filter list-filter-sort">
                <i class="fa-solid fa-arrow-down-wide-short"></i>
                <select name="tri" data-auto-submit aria-label="Trier par">
                    @foreach (UserController::SORTS as $value => $label)
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
                    <th>Utilisateur</th>
                    <th>Rôle</th>
                    <th>Activité</th>
                    <th>Statut</th>
                    <th>Action</th>
                </tr>
            </thead>

            <tbody>
                @forelse ($users as $user)
                    <tr class="{{ $user->statut->value === 'suspended' ? 'is-muted-row' : '' }}">
                        <td class="cell-main">
                            <div class="cell-entity">
                                <img src="{{ $user->avatarUrl() }}" alt="" class="cell-avatar" loading="lazy">
                                <span class="cell-entity-text">
                                    <a href="{{ route('admin.utilisateurs.show', $user) }}" class="cell-title-link"><strong>{{ $user->name }}</strong></a>
                                    <small>
                                        {{ $user->email }}
                                        @if ($user->phone)
                                            · <span class="text-nowrap">{{ $user->formattedPhone() }}</span>
                                        @endif
                                    </small>
                                    @if ($user->company_name || $user->city)
                                        <small class="cell-muted">{{ collect([$user->company_name, $user->city])->filter()->implode(' · ') }}</small>
                                    @endif
                                </span>
                            </div>
                        </td>
                        <td><span class="status-pill status-{{ $user->role->tone() }}">{{ $user->role->label() }}</span></td>
                        <td>
                            <span class="cell-stack">
                                <span class="text-nowrap">
                                    @if ($user->isOwner())
                                        {{ $user->properties_count }} établissement{{ $user->properties_count > 1 ? 's' : '' }}
                                    @elseif ($user->isAdmin())
                                        Administration
                                    @else
                                        {{ $user->reservations_count }} réservation{{ $user->reservations_count > 1 ? 's' : '' }}
                                    @endif
                                </span>
                                <small @if ($user->last_login_at) title="{{ $user->last_login_at->translatedFormat('d F Y à H:i') }}" @endif>
                                    {{ $user->last_login_at ? 'Vu '.$user->last_login_at->diffForHumans() : 'Jamais connecté' }}
                                </small>
                            </span>
                        </td>
                        <td>
                            <span class="status-pill status-{{ $user->statut->tone() }}">{{ $user->statut->label() }}</span>
                            @unless ($user->hasVerifiedEmail())
                                <small class="cell-hint">Email non confirmé</small>
                            @endunless
                        </td>
                        <td>
                            <a href="{{ route('admin.utilisateurs.show', $user) }}" class="action-btn view" title="Voir la fiche" aria-label="Voir la fiche de {{ $user->name }}">
                                <i class="fa-solid fa-eye"></i>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="empty-table">
                            @include('admin.partials.empty-state', ['icon' => 'fa-users', 'search' => $search])
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @include('admin.partials.pagination', ['paginator' => $users, 'label' => 'utilisateur(s)'])
@endsection
