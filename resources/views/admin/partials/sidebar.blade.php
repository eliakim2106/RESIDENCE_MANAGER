@php
    use App\Enums\UserRole;

    $user = auth()->user();

    $backOffice = [UserRole::SuperAdmin, UserRole::Admin];
    $management = [UserRole::SuperAdmin, UserRole::Admin, UserRole::Owner];
    $everyone = UserRole::cases();

    // Réservations en attente de confirmation (établissements du propriétaire, ou toute la plateforme)
    $pendingReservations = $user->hasRole(...$management)
        ? App\Models\Reservation::query()
            ->where('status', App\Enums\ReservationStatus::Pending)
            ->unless($user->isAdmin(), fn ($query) => $query->whereHas('property', fn ($query) => $query->ownedBy($user)))
            ->count()
        : 0;

    // Établissements soumis par les propriétaires, en attente d'un administrateur
    $pendingProperties = $user->isAdmin()
        ? App\Models\Property::query()->where('status', App\Enums\PropertyStatus::Pending)->count()
        : 0;

    // Chaque lien : titre, icône, route, préfixe de route pour l'état actif, rôles autorisés, compteur (facultatif)
    $sections = [
        'Activité' => [
            [$user->hasRole(...$management) ? 'Réservations' : 'Mes réservations', 'fa-solid fa-calendar-check', 'admin.reservations.index', 'admin.reservations.', $everyone, $pendingReservations],
            ['Paiements', 'fa-solid fa-wallet', 'admin.paiements.index', 'admin.paiements.', $management],
        ],
        'Hébergements' => [
            ['Établissements', 'fa-solid fa-building', 'admin.etablissements.index', 'admin.etablissements.', $management],
            ['Unités', 'fa-solid fa-door-open', 'admin.unites.index', 'admin.unites.', $management],
            ['Validations', 'fa-solid fa-building-circle-check', 'admin.validations.index', 'admin.validations.', $backOffice, $pendingProperties],
        ],
        'Référentiels' => [
            ["Types d'établissement", 'fa-solid fa-hotel', 'admin.types-etablissement.index', 'admin.types-etablissement.', $backOffice],
            ["Types d'unité", 'fa-solid fa-bed', 'admin.types-unite.index', 'admin.types-unite.', $backOffice],
            ['Équipements', 'fa-solid fa-wifi', 'admin.equipements.index', 'admin.equipements.', $backOffice],
        ],
    ];
@endphp

<aside class="sidebar" id="sidebar" aria-label="Menu principal">

    <div class="sidebar-logo">
        <a href="{{ route('dashboard') }}">
            <img src="{{ asset('assets/images/logo/ds_holding_logo.png') }}" alt="DS HOLDING">
        </a>

        <button type="button" class="sidebar-close" data-sidebar-close aria-label="Fermer le menu">
            <i class="fa-solid fa-xmark"></i>
        </button>
    </div>

    <nav class="sidebar-nav">

        <a href="{{ route('dashboard') }}" class="menu-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" title="Tableau de bord">
            <i class="fa-solid fa-gauge-high"></i>
            <span>Tableau de bord</span>
        </a>

        @foreach ($sections as $title => $items)
            @php
                $visibleItems = array_filter($items, fn (array $item): bool => $user->hasRole(...$item[4]));
            @endphp

            @if ($visibleItems !== [])
                <div class="menu-section">
                    <h4>{{ $title }}</h4>

                    @foreach ($visibleItems as $item)
                        @php
                            [$label, $icon, $route, $prefix] = $item;
                            $badge = $item[5] ?? 0;
                        @endphp
                        <a href="{{ route($route) }}" class="menu-link {{ request()->routeIs($prefix.'*') ? 'active' : '' }}" title="{{ $label }}">
                            <i class="{{ $icon }}"></i>
                            <span>{{ $label }}</span>
                            @if ($badge > 0)
                                <span class="menu-badge" aria-label="{{ $badge }} en attente">{{ $badge }}</span>
                            @endif
                        </a>
                    @endforeach
                </div>
            @endif
        @endforeach

    </nav>

    {{-- Carte du compte connecté (la déconnexion se trouve dans le menu du profil, en haut à droite) --}}
    @php
        $avatarFallback = 'https://ui-avatars.com/api/?background=d4a72c&color=0a1f44&bold=true&name='.urlencode($user->name);
        $avatar = $user->avatar_path
            ? \Illuminate\Support\Facades\Storage::disk('public')->url($user->avatar_path)
            : $avatarFallback;
    @endphp

    <div class="sidebar-footer">
        <div class="sidebar-profile" title="{{ $user->name }} · {{ $user->email }}">
            <img src="{{ $avatar }}" alt="{{ $user->name }}" class="sidebar-profile-avatar"
                onerror="this.onerror=null;this.src='{{ $avatarFallback }}'">
            <div class="sidebar-profile-info">
                <div class="sidebar-profile-name">{{ $user->role->label() }}</div>
                <div class="sidebar-profile-email">{{ $user->email }}</div>
            </div>
        </div>
    </div>

</aside>
