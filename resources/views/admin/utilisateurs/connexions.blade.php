@extends('layouts.admin')

@section('title', 'Journal des connexions')

@section('content')
    <div class="admin-page-header">
        <div class="admin-page-heading">
            <span class="admin-page-icon"><i class="fa-solid fa-shield-halved"></i></span>
            <div>
                <h1>Journal des connexions</h1>
                <p>Chaque tentative de connexion à la plateforme, réussie ou non.</p>
            </div>
        </div>

        <div class="admin-page-actions">
            <a href="{{ route('admin.utilisateurs.index') }}" class="btn-secondary">
                <i class="fa-solid fa-users"></i>
                Utilisateurs
            </a>
        </div>
    </div>

    @include('partials.flash')

    @if ($recentFailures > 0)
        <div class="moderation-banner {{ $recentFailures >= 10 ? 'tone-critical' : 'tone-warning' }}" role="status">
            <i class="fa-solid fa-triangle-exclamation"></i>
            <div>
                <strong>{{ $recentFailures }} tentative{{ $recentFailures > 1 ? 's' : '' }} échouée{{ $recentFailures > 1 ? 's' : '' }} ces dernières 24 heures</strong>
                <p>Plusieurs échecs sur une même adresse ou une même IP peuvent signaler une tentative d’accès non autorisé.</p>
            </div>
        </div>
    @endif

    @include('admin.partials.list-toolbar', [
        'tabs' => $tabs,
        'counts' => $counts,
        'statut' => $tab,
        'search' => $search,
        'placeholder' => 'Email, nom, adresse IP…',
    ])

    <div class="table-card">
        <table class="custom-table">
            <thead>
                <tr>
                    <th>Compte</th>
                    <th>Appareil</th>
                    <th>Adresse IP</th>
                    <th>Date</th>
                    <th>Résultat</th>
                </tr>
            </thead>

            <tbody>
                @forelse ($logs as $log)
                    <tr>
                        <td class="cell-main">
                            <span class="cell-entity-text">
                                @if ($log->user)
                                    <a href="{{ route('admin.utilisateurs.show', $log->user) }}" class="cell-title-link"><strong>{{ $log->user->name }}</strong></a>
                                @else
                                    <strong>Compte inconnu</strong>
                                @endif
                                <small>{{ $log->email }}</small>
                            </span>
                        </td>
                        <td class="text-nowrap"><i class="fa-solid {{ $log->deviceIcon() }} cell-muted"></i> {{ $log->device() }}</td>
                        <td><a href="{{ request()->fullUrlWithQuery(['search' => $log->ip_address, 'page' => null]) }}" class="cell-link cell-mono" title="Voir les tentatives de cette adresse">{{ $log->ip_address ?? '—' }}</a></td>
                        <td class="text-nowrap">
                            <span class="cell-stack">
                                <span>{{ $log->created_at?->translatedFormat('d M Y à H:i') }}</span>
                                <small>{{ $log->created_at?->diffForHumans() }}</small>
                            </span>
                        </td>
                        <td>
                            @if ($log->successful)
                                <span class="status-pill status-good">Réussie</span>
                            @else
                                <span class="status-pill status-critical">Échouée</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="empty-table">
                            @include('admin.partials.empty-state', ['icon' => 'fa-shield-halved', 'search' => $search])
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @include('admin.partials.pagination', ['paginator' => $logs, 'label' => 'connexion(s)'])
@endsection
