@extends('layouts.admin')

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
            <a href="{{ route('admin.utilisateurs.connexions') }}" class="btn-secondary">
                <i class="fa-solid fa-shield-halved"></i>
                Journal des connexions
            </a>
            <form method="GET" class="list-filter" aria-label="Filtrer par état du compte">
                @foreach (request()->except(['etat', 'page']) as $name => $value)
                    @if (is_string($value) && $value !== '')
                        <input type="hidden" name="{{ $name }}" value="{{ $value }}">
                    @endif
                @endforeach
                <i class="fa-solid fa-user-check"></i>
                <select name="etat" data-auto-submit aria-label="État du compte">
                    <option value="">Tous les états</option>
                    <option value="actifs" @selected($state === 'actifs')>Actifs</option>
                    <option value="en-attente" @selected($state === 'en-attente')>En attente</option>
                    <option value="suspendus" @selected($state === 'suspendus')>Suspendus</option>
                </select>
                <noscript><button type="submit" class="btn-secondary">Filtrer</button></noscript>
            </form>
        </div>
    </div>

    @include('partials.flash')

    @include('admin.partials.list-toolbar', [
        'tabs' => $tabs,
        'counts' => $counts,
        'statut' => $tab,
        'search' => $search,
        'placeholder' => 'Nom, email, téléphone, entreprise…',
    ])

    <div class="table-card">
        <table class="custom-table">
            <thead>
                <tr>
                    <th>Utilisateur</th>
                    <th>Rôle</th>
                    <th>Contact</th>
                    <th>Activité</th>
                    <th>Dernière connexion</th>
                    <th>Statut</th>
                    <th><span class="visually-hidden">Actions</span></th>
                </tr>
            </thead>

            <tbody>
                @forelse ($users as $user)
                    <tr>
                        <td class="cell-main">
                            <div class="cell-entity">
                                <img src="{{ $user->avatarUrl() }}" alt="" class="cell-avatar" loading="lazy">
                                <span class="cell-entity-text">
                                    <a href="{{ route('admin.utilisateurs.show', $user) }}" class="cell-title-link"><strong>{{ $user->name }}</strong></a>
                                    <small>{{ $user->email }}</small>
                                </span>
                            </div>
                        </td>
                        <td><span class="status-pill status-{{ $user->role->tone() }}">{{ $user->role->label() }}</span></td>
                        <td>
                            <span class="cell-stack">
                                <span class="text-nowrap">{{ $user->phone ?: '—' }}</span>
                                <small>{{ collect([$user->company_name, $user->city])->filter()->implode(' · ') }}</small>
                            </span>
                        </td>
                        <td class="text-nowrap">
                            @if ($user->isOwner())
                                {{ $user->properties_count }} établissement{{ $user->properties_count > 1 ? 's' : '' }}
                            @elseif ($user->isAdmin())
                                <span class="cell-muted">—</span>
                            @else
                                {{ $user->reservations_count }} réservation{{ $user->reservations_count > 1 ? 's' : '' }}
                            @endif
                        </td>
                        <td class="text-nowrap">
                            @if ($user->last_login_at)
                                <span title="{{ $user->last_login_at->translatedFormat('d F Y à H:i') }}">{{ $user->last_login_at->diffForHumans() }}</span>
                            @else
                                <span class="cell-muted">Jamais</span>
                            @endif
                        </td>
                        <td>
                            <span class="status-pill status-{{ $user->status->tone() }}">{{ $user->status->label() }}</span>
                            @unless ($user->hasVerifiedEmail())
                                <small class="cell-hint">Email non confirmé</small>
                            @endunless
                        </td>
                        <td>
                            <a href="{{ route('admin.utilisateurs.show', $user) }}" class="action-btn edit" title="Voir la fiche" aria-label="Voir la fiche de {{ $user->name }}">
                                <i class="fa-solid fa-eye"></i>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="empty-table">
                            @include('admin.partials.empty-state', ['icon' => 'fa-users', 'search' => $search])
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @include('admin.partials.pagination', ['paginator' => $users, 'label' => 'utilisateur(s)'])
@endsection
