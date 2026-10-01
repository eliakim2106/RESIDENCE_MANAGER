@php
    use App\Enums\UserRole;

    $user = auth()->user();

    $backOffice = [UserRole::SuperAdmin, UserRole::Admin];
    $management = [UserRole::SuperAdmin, UserRole::Admin, UserRole::Owner];
    $everyone = UserRole::cases();

    // Réservations en attente de confirmation (établissements du propriétaire, ou toute la plateforme)
    $pendingReservations = $user->hasRole(...$management)
        ? App\Models\Reservation::query()
            ->where('statut', App\Enums\ReservationStatus::Pending)
            ->unless($user->isAdmin(), fn ($query) => $query->whereHas('property', fn ($query) => $query->ownedBy($user)))
            ->count()
        : 0;

    // Établissements soumis par les propriétaires, en attente d'un administrateur
    $pendingProperties = $user->isAdmin()
        ? App\Models\Property::query()->where('statut', App\Enums\PropertyStatus::Pending)->count()
        : 0;

    // Factures d'abonnement à encaisser (administrateurs)
    $unpaidInvoices = $user->isAdmin()
        ? App\Models\SubscriptionInvoice::query()->where('statut', App\Enums\InvoiceStatus::Unpaid)->count()
        : 0;

    // Messages du formulaire de contact pas encore lus (administrateurs)
    $newMessages = $user->isAdmin()
        ? App\Models\ContactMessage::query()->where('statut', App\Enums\ContactMessageStatus::New)->count()
        : 0;

    // Chaque lien : titre, icône, route, préfixe de route pour l'état actif, rôles autorisés, compteur (facultatif)
    $sections = [
        'Activité' => [
            [$user->hasRole(...$management) ? 'Réservations' : 'Mes réservations', 'fa-solid fa-calendar-check', 'admin.reservations.index', 'admin.reservations.', $everyone, $pendingReservations],
            ['Paiements', 'fa-solid fa-wallet', 'admin.paiements.index', 'admin.paiements.', $management],
            ['Reversements', 'fa-solid fa-hand-holding-dollar', 'admin.reversements.index', 'admin.reversements.', $backOffice],
            ['Mes reversements', 'fa-solid fa-hand-holding-dollar', 'admin.mes-reversements.index', 'admin.mes-reversements.', [UserRole::Owner]],
            ['Mon abonnement', 'fa-solid fa-id-card', 'admin.abonnement.show', 'admin.abonnement.', [UserRole::Owner]],
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
        'Administration' => [
            ['Utilisateurs', 'fa-solid fa-users', 'admin.utilisateurs.index', 'admin.utilisateurs.', $backOffice],
            ['Abonnements', 'fa-solid fa-id-card', 'admin.abonnements.index', 'admin.abonnements.', $backOffice, $unpaidInvoices],
            ['Formules', 'fa-solid fa-layer-group', 'admin.formules.index', 'admin.formules.', $backOffice],
            ['Messages', 'fa-solid fa-envelope', 'admin.messages.index', 'admin.messages.', $backOffice, $newMessages],
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

    {{-- Carte du compte connecté : ouvre « Mon profil » (la déconnexion se trouve dans le menu du profil, en haut à droite) --}}
    <div class="sidebar-footer">
        <a href="{{ route('admin.profil.edit') }}" class="sidebar-profile {{ request()->routeIs('admin.profil.*') ? 'active' : '' }}" title="Mon profil · {{ $user->email }}">
            <img src="{{ $user->avatarUrl() }}" alt="{{ $user->name }}" class="sidebar-profile-avatar">
            <div class="sidebar-profile-info">
                <div class="sidebar-profile-name">{{ $user->role->label() }}</div>
                <div class="sidebar-profile-email">{{ $user->email }}</div>
            </div>
        </a>
    </div>

</aside>
