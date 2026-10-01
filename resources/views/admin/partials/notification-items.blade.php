{{-- Notifications non lues du menu cloche (aussi renvoyées par le flux pour la mise à jour en direct). Paramètre : $items --}}
@forelse ($items as $notification)
    <a href="{{ route('admin.notifications.open', $notification->id) }}" class="admin-dropdown-item notification-item">
        <i class="fa-solid {{ $notification->data['icon'] ?? 'fa-bell' }} tone-{{ $notification->data['tone'] ?? 'info' }}"></i>
        <span>
            {{ $notification->data['message'] ?? 'Nouvelle notification' }}
            <small title="{{ $notification->created_at->translatedFormat('d F Y à H:i') }}">{{ $notification->created_at->gt(now()->subMinute()) ? 'À l’instant' : $notification->created_at->diffForHumans() }}</small>
        </span>
    </a>
@empty
    <div class="admin-dropdown-empty">
        <i class="fa-regular fa-bell-slash"></i>
        <span>Aucune nouvelle notification</span>
    </div>
@endforelse
