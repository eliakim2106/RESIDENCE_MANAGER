{{--
    Espace client (hors administration) : barre du site, menu du compte à gauche, contenu à droite.
    Sections : title, account_heading (titre de la page), account_subtitle, account_actions (facultatif), account
--}}
@extends('layouts.site')

@php
    $client = auth()->user();
    $menu = [
        ['client.dashboard', 'client.dashboard', 'fa-house', 'Tableau de bord', null],
        ['client.reservations.index', 'client.reservations.*', 'fa-calendar-check', 'Mes réservations', $client->reservations()->whereIn('statut', [App\Enums\ReservationStatus::Pending, App\Enums\ReservationStatus::Confirmed])->whereDate('check_out', '>=', now()->toDateString())->count()],
        ['client.favorites.index', 'client.favorites.*', 'fa-heart', 'Mes favoris', null],
        ['client.notifications', 'client.notifications', 'fa-bell', 'Notifications', $client->unreadNotifications()->count()],
        ['client.profile.edit', 'client.profile.edit', 'fa-user', 'Mon profil', null],
    ];
@endphp

@section('content')
    @include('site.partials.navbar')

    <div class="account-page">
        <div class="container account-container">

            <aside class="account-sidebar" aria-label="Mon compte">
                <div class="account-user">
                    <img src="{{ $client->avatarUrl() }}" alt="" class="account-user-avatar">
                    <div>
                        <strong>{{ $client->name }}</strong>
                        <small>Client depuis {{ $client->created_at->translatedFormat('F Y') }}</small>
                    </div>
                </div>

                <nav class="account-nav">
                    @foreach ($menu as [$route, $pattern, $icon, $label, $badge])
                        <a href="{{ route($route) }}" class="account-nav-link {{ request()->routeIs($pattern) ? 'is-active' : '' }}" @if (request()->routeIs($pattern)) aria-current="page" @endif>
                            <i class="fa-solid {{ $icon }}"></i>
                            <span>{{ $label }}</span>
                            @if ($badge)
                                <span class="account-nav-badge">{{ $badge }}</span>
                            @endif
                        </a>
                    @endforeach
                </nav>

                <a href="{{ route('residences.index') }}" class="account-cta">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    Trouver une résidence
                </a>

                <form method="POST" action="{{ route('logout') }}" class="account-logout">
                    @csrf
                    <button type="submit"><i class="fa-solid fa-arrow-right-from-bracket"></i> Se déconnecter</button>
                </form>
            </aside>

            <main class="account-main" id="contenu">
                <header class="account-heading">
                    <div>
                        <h1>@yield('account_heading')</h1>
                        @hasSection('account_subtitle')
                            <p>@yield('account_subtitle')</p>
                        @endif
                    </div>
                    @hasSection('account_actions')
                        <div class="account-heading-actions">@yield('account_actions')</div>
                    @endif
                </header>

                @include('partials.flash')

                @yield('account')
            </main>

        </div>
    </div>

    @include('site.partials.footer')
@endsection
