@extends('layouts.admin')

@section('title', 'Notifications')

@section('content')
    <div class="admin-page-header">
        <div class="admin-page-heading">
            <span class="admin-page-icon"><i class="fa-solid fa-bell"></i></span>
            <div>
                <h1>Notifications</h1>
                <p>Réservations, paiements, abonnement, reversements : tout ce qui concerne votre compte.</p>
            </div>
        </div>

        <div class="admin-page-actions">
            @if ($counts['non-lues'] > 0)
                <form method="POST" action="{{ route('admin.notifications.read-all') }}">
                    @csrf
                    <button type="submit" class="btn-secondary"><i class="fa-solid fa-check-double"></i> Tout marquer comme lu</button>
                </form>
            @endif
            @if ($counts['toutes'] > $counts['non-lues'])
                <form method="POST" action="{{ route('admin.notifications.destroy-read') }}" data-confirm="Supprimer toutes les notifications déjà lues ?">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn-outline-danger"><i class="fa-solid fa-trash-can"></i> Supprimer les lues</button>
                </form>
            @endif
        </div>
    </div>

    @include('partials.flash')

    <section class="resa-filters">
        <div class="list-toolbar">
            <nav class="status-tabs" aria-label="Filtrer les notifications">
                @foreach (['non-lues' => 'Non lues', 'toutes' => 'Toutes'] as $key => $label)
                    <a href="{{ route('admin.notifications.index', $key === 'non-lues' ? [] : ['statut' => $key]) }}"
                        class="status-tab {{ $tab === $key ? 'is-active' : '' }}" @if ($tab === $key) aria-current="page" @endif>
                        {{ $label }}
                        <span>{{ $counts[$key] }}</span>
                    </a>
                @endforeach
            </nav>
        </div>
    </section>

    <div class="table-card notification-page">
        @if ($notifications->isEmpty())
            <div class="empty-state">
                <i class="fa-regular fa-bell-slash"></i>
                @if ($tab === 'non-lues')
                    <strong>Vous êtes à jour</strong>
                    <span>Aucune notification non lue.</span>
                    @if ($counts['toutes'] > 0)
                        <a href="{{ route('admin.notifications.index', ['statut' => 'toutes']) }}" class="btn-secondary">Voir les notifications lues</a>
                    @endif
                @else
                    <strong>Aucune notification</strong>
                    <span>Les nouvelles réservations, paiements et décisions vous seront signalés ici.</span>
                @endif
            </div>
        @else
            <ul class="notification-feed">
                @foreach ($notifications as $notification)
                    @php
                        $tone = $notification->data['tone'] ?? 'info';
                        $unread = $notification->read_at === null;
                    @endphp
                    <li class="{{ $unread ? 'is-unread' : '' }}">
                        <span class="notification-feed-icon tone-{{ $tone }}"><i class="fa-solid {{ $notification->data['icon'] ?? 'fa-bell' }}"></i></span>

                        <a href="{{ route('admin.notifications.open', $notification->id) }}" class="notification-feed-text">
                            <strong>{{ $notification->data['message'] ?? 'Nouvelle notification' }}</strong>
                            <small title="{{ $notification->created_at->translatedFormat('d F Y à H:i') }}">
                                {{ $notification->created_at->gt(now()->subMinute()) ? 'À l’instant' : $notification->created_at->diffForHumans() }} · {{ $notification->created_at->format('d/m/Y H:i') }}
                            </small>
                        </a>

                        <span class="invoice-actions">
                            @if ($unread)
                                <span class="notification-dot" aria-label="Non lue"></span>
                                <form method="POST" action="{{ route('admin.notifications.read', $notification->id) }}" class="inline-action">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="action-btn" title="Marquer comme lue" aria-label="Marquer comme lue">
                                        <i class="fa-solid fa-check"></i>
                                    </button>
                                </form>
                            @endif
                            <form method="POST" action="{{ route('admin.notifications.destroy', $notification->id) }}" class="inline-action">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="action-btn delete" title="Supprimer" aria-label="Supprimer la notification">
                                    <i class="fa-solid fa-trash-can"></i>
                                </button>
                            </form>
                        </span>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>

    @include('admin.partials.pagination', ['paginator' => $notifications, 'label' => 'notification(s)'])
@endsection
