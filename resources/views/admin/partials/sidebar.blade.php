@php
    use App\Enums\UserRole;

    $backOffice = [UserRole::SuperAdmin, UserRole::Admin];
    $management = [UserRole::SuperAdmin, UserRole::Admin, UserRole::Owner];

    // Chaque lien : titre, icône, route, préfixe de route pour l'état actif, rôles autorisés
    $sections = [
        'Hébergements' => [
            ['Établissements', 'fa-solid fa-building', 'admin.etablissements.index', 'admin.etablissements.', $management],
            ['Unités', 'fa-solid fa-door-open', 'admin.unites.index', 'admin.unites.', $management],
        ],
        'Référentiels' => [
            ["Types d'établissement", 'fa-solid fa-hotel', 'admin.types-etablissement.index', 'admin.types-etablissement.', $backOffice],
            ["Types d'unité", 'fa-solid fa-bed', 'admin.types-unite.index', 'admin.types-unite.', $backOffice],
            ['Équipements', 'fa-solid fa-wifi', 'admin.equipements.index', 'admin.equipements.', $backOffice],
        ],
    ];

    $user = auth()->user();
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

                    @foreach ($visibleItems as [$label, $icon, $route, $prefix])
                        <a href="{{ route($route) }}" class="menu-link {{ request()->routeIs($prefix.'*') ? 'active' : '' }}" title="{{ $label }}">
                            <i class="{{ $icon }}"></i>
                            <span>{{ $label }}</span>
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
