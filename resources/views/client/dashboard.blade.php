@extends('layouts.account')

@php
    $money = fn (int $amount): string => number_format($amount, 0, ',', ' ').' FCFA';
    $firstName = Str::before($user->name, ' ');
@endphp

@section('title', 'Mon espace')
@section('account_heading', 'Bonjour '.$firstName)
@section('account_subtitle', 'Retrouvez vos séjours, vos paiements et vos résidences préférées.')

@section('account')
    {{-- ========== Prochain séjour ========== --}}
    @if ($nextStay)
        @php
            $property = $nextStay->property;
            $daysLeft = (int) now()->startOfDay()->diffInDays($nextStay->check_in, false);
        @endphp
        <section class="acc-next-stay">
            <img src="{{ $property?->coverImage?->url ?? asset('assets/images/home/residence-1.webp') }}" alt="" class="acc-next-stay-bg">
            <div class="acc-next-stay-content">
                <span class="acc-kicker">
                    @if ($daysLeft > 1)
                        Votre prochain séjour · dans {{ $daysLeft }} jours
                    @elseif ($daysLeft === 1)
                        Votre prochain séjour · demain
                    @elseif ($daysLeft === 0)
                        Arrivée aujourd’hui
                    @else
                        Séjour en cours
                    @endif
                </span>
                <h2>{{ $property?->name }}</h2>
                <p>
                    <i class="fa-regular fa-calendar"></i> Du {{ $nextStay->check_in->translatedFormat('l d F') }} au {{ $nextStay->check_out->translatedFormat('l d F Y') }}
                    · {{ $nextStay->nights }} nuit{{ $nextStay->nights > 1 ? 's' : '' }}
                </p>
                <div class="acc-next-stay-actions">
                    <span class="acc-pill tone-{{ $nextStay->statut->tone() }}">{{ $nextStay->statut->label() }}</span>
                    <a href="{{ route('client.reservations.show', $nextStay) }}" class="acc-btn acc-btn-gold">Voir ma réservation <i class="fa-solid fa-arrow-right"></i></a>
                    @if ($nextStay->balanceDue() > 0)
                        <span class="acc-next-stay-due">Reste {{ $money($nextStay->balanceDue()) }} à régler</span>
                    @endif
                </div>
            </div>
        </section>
    @else
        <section class="acc-empty-hero">
            <i class="fa-solid fa-suitcase-rolling"></i>
            <div>
                <h2>Aucun séjour prévu pour le moment</h2>
                <p>Résidences meublées, villas et hôtels en Côte d’Ivoire : trouvez votre prochaine adresse.</p>
            </div>
            <a href="{{ route('residences.index') }}" class="acc-btn acc-btn-gold">Explorer les résidences <i class="fa-solid fa-arrow-right"></i></a>
        </section>
    @endif

    {{-- ========== Chiffres ========== --}}
    <div class="acc-stats">
        <a href="{{ route('client.reservations.index') }}" class="acc-stat">
            <i class="fa-solid fa-calendar-check"></i>
            <strong>{{ $stats['upcoming'] }}</strong>
            <span>Séjour{{ $stats['upcoming'] > 1 ? 's' : '' }} à venir</span>
        </a>
        <a href="{{ route('client.reservations.index', ['onglet' => 'passees']) }}" class="acc-stat">
            <i class="fa-solid fa-moon"></i>
            <strong>{{ $stats['nights'] }}</strong>
            <span>Nuit{{ $stats['nights'] > 1 ? 's' : '' }} passée{{ $stats['nights'] > 1 ? 's' : '' }} avec nous</span>
        </a>
        <a href="{{ route('client.reservations.index') }}" class="acc-stat {{ $toPay > 0 ? 'is-alert' : '' }}">
            <i class="fa-solid fa-wallet"></i>
            <strong>{{ $toPay > 0 ? $money($toPay) : '0' }}</strong>
            <span>{{ $toPay > 0 ? 'À régler sur '.$toPayCount.' réservation'.($toPayCount > 1 ? 's' : '') : 'Rien à régler' }}</span>
        </a>
        <a href="{{ route('client.favorites.index') }}" class="acc-stat">
            <i class="fa-solid fa-heart"></i>
            <strong>{{ $stats['favorites'] }}</strong>
            <span>Résidence{{ $stats['favorites'] > 1 ? 's' : '' }} en favoris</span>
        </a>
    </div>

    <div class="acc-grid">
        <div class="acc-col">
            {{-- Avis à donner --}}
            @if ($toReview->isNotEmpty())
                <section class="acc-card acc-card-highlight">
                    <div class="acc-card-head">
                        <h2><i class="fa-solid fa-star"></i> Votre avis compte</h2>
                    </div>
                    <ul class="acc-list">
                        @foreach ($toReview->take(3) as $reservation)
                            <li>
                                <img src="{{ $reservation->property?->coverImage?->url ?? asset('assets/images/home/residence-1.webp') }}" alt="">
                                <span>
                                    <strong>{{ $reservation->property?->name }}</strong>
                                    <small>Séjour terminé le {{ $reservation->check_out->translatedFormat('d F Y') }}</small>
                                </span>
                                <a href="{{ route('client.reservations.show', $reservation) }}#avis" class="acc-btn acc-btn-light">Noter</a>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif

            {{-- Autres séjours à venir --}}
            <section class="acc-card">
                <div class="acc-card-head">
                    <h2>Autres séjours à venir</h2>
                    <a href="{{ route('client.reservations.index') }}">Toutes mes réservations <i class="fa-solid fa-arrow-right"></i></a>
                </div>
                @forelse ($upcoming as $reservation)
                    @include('client.partials.reservation-card', ['reservation' => $reservation])
                @empty
                    <p class="acc-muted">{{ $nextStay ? 'Pas d’autre séjour prévu.' : 'Vos prochaines réservations apparaîtront ici.' }}</p>
                @endforelse
            </section>
        </div>

        {{-- Activité récente --}}
        <section class="acc-card">
            <div class="acc-card-head">
                <h2>Activité récente</h2>
                <a href="{{ route('client.notifications') }}">Tout voir <i class="fa-solid fa-arrow-right"></i></a>
            </div>
            @if ($notifications->isEmpty())
                <p class="acc-muted">Les confirmations, paiements et messages de vos établissements apparaîtront ici.</p>
            @else
                <ul class="acc-feed">
                    @foreach ($notifications as $notification)
                        <li class="{{ $notification->read_at ? '' : 'is-unread' }}">
                            <span class="acc-feed-icon tone-{{ $notification->data['tone'] ?? 'info' }}"><i class="fa-solid {{ $notification->data['icon'] ?? 'fa-bell' }}"></i></span>
                            <a href="{{ route('admin.notifications.open', $notification->id) }}">
                                {{ $notification->data['message'] ?? 'Notification' }}
                                <small>{{ $notification->created_at->diffForHumans() }}</small>
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>
    </div>
@endsection
