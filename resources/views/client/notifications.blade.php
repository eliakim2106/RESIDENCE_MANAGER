@extends('layouts.account')

@section('title', 'Notifications')
@section('account_heading', 'Notifications')
@section('account_subtitle', 'Confirmations, paiements, remboursements : tout ce qui concerne vos séjours.')

@section('account_actions')
    @if ($unread > 0)
        <form method="POST" action="{{ route('admin.notifications.read-all') }}">
            @csrf
            <button type="submit" class="acc-btn acc-btn-light"><i class="fa-solid fa-check-double"></i> Tout marquer comme lu</button>
        </form>
    @endif
@endsection

@section('account')
    @if ($notifications->isEmpty())
        <div class="acc-empty">
            <i class="fa-regular fa-bell-slash"></i>
            <strong>Aucune notification</strong>
            <p>Nous vous préviendrons ici à chaque étape de vos réservations.</p>
        </div>
    @else
        <section class="acc-card">
            <ul class="acc-feed acc-feed-large">
                @foreach ($notifications as $notification)
                    <li class="{{ $notification->read_at ? '' : 'is-unread' }}">
                        <span class="acc-feed-icon tone-{{ $notification->data['tone'] ?? 'info' }}"><i class="fa-solid {{ $notification->data['icon'] ?? 'fa-bell' }}"></i></span>
                        <a href="{{ route('admin.notifications.open', $notification->id) }}">
                            {{ $notification->data['message'] ?? 'Notification' }}
                            <small>{{ $notification->created_at->diffForHumans() }} · {{ $notification->created_at->format('d/m/Y H:i') }}</small>
                        </a>
                    </li>
                @endforeach
            </ul>
        </section>

        @if ($notifications->hasPages())
            <div class="acc-pagination">{{ $notifications->links('pagination::bootstrap-5') }}</div>
        @endif
    @endif
@endsection
