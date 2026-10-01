@php
    $user = auth()->user();
    $photo = $user->avatarUrl();
    $notifications = $user->unreadNotifications()->count();

    // Fil d'Ariane : section d'après le nom de la route, puis la page en cours (section « title »)
    $sections = [
        'admin.reservations.' => [
            $user->isAdmin() || $user->isOwner() ? 'Réservations' : 'Mes réservations',
            'admin.reservations.index',
        ],
        'admin.paiements.' => ['Paiements', 'admin.paiements.index'],
        'admin.reversements.' => [
            $user->isAdmin() ? 'Reversements' : 'Mes reversements',
            $user->isAdmin() ? 'admin.reversements.index' : 'admin.mes-reversements.index',
        ],
        'admin.mes-reversements.' => ['Mes reversements', 'admin.mes-reversements.index'],
        'admin.validations.' => ['Validations', 'admin.validations.index'],
        'admin.utilisateurs.' => ['Utilisateurs', 'admin.utilisateurs.index'],
        'admin.abonnements.' => ['Abonnements', 'admin.abonnements.index'],
        'admin.formules.' => ['Formules', 'admin.formules.index'],
        'admin.abonnement.' => ['Mon abonnement', 'admin.abonnement.show'],
        'admin.profil.' => ['Mon profil', 'admin.profil.edit'],
        'admin.notifications.' => ['Notifications', 'admin.notifications.index'],
        'admin.etablissements.unites.' => ['Établissements', 'admin.etablissements.index'],
        'admin.etablissements.' => ['Établissements', 'admin.etablissements.index'],
        'admin.unites.' => ['Unités', 'admin.unites.index'],
        'admin.types-etablissement.' => ["Types d'établissement", 'admin.types-etablissement.index'],
        'admin.types-unite.' => ["Types d'unité", 'admin.types-unite.index'],
        'admin.equipements.' => ['Équipements', 'admin.equipements.index'],
        'admin.messages.' => ['Messages', 'admin.messages.index'],
        'admin.avis.' => ['Avis', 'admin.avis.index'],
        'admin.parametres.' => ['Paramètres du site', 'admin.parametres.edit'],
    ];

    $routeName = (string) request()->route()?->getName();
    $crumbs = [['Tableau de bord', route('dashboard')]];

    foreach ($sections as $prefix => [$label, $indexRoute]) {
        if (str_starts_with($routeName, $prefix)) {
            $crumbs[] = [$label, $routeName === $indexRoute ? null : route($indexRoute)];

            if ($routeName !== $indexRoute) {
                // Le titre de la section est déjà échappé : on le décode pour ne pas l'échapper deux fois
            $crumbs[] = [html_entity_decode(trim($__env->yieldContent('title')), ENT_QUOTES | ENT_HTML5), null];
            }

            break;
        }
    }

    if (count($crumbs) === 1) {
        $crumbs[0][1] = null;
    }
@endphp

<header class="navbar">

    <div class="navbar-left">

        <button type="button" class="menu-toggle" aria-label="Afficher ou masquer le menu" aria-controls="sidebar">
            <i class="fa-solid fa-bars"></i>
        </button>

        <nav aria-label="Fil d’Ariane" class="admin-breadcrumb">
            <ol>
                @foreach ($crumbs as [$label, $url])
                    <li @if ($loop->last) aria-current="page" @endif>
                        @if ($url)
                            <a href="{{ $url }}">{{ $label }}</a>
                        @else
                            <span>{{ $label }}</span>
                        @endif
                    </li>
                @endforeach
            </ol>
        </nav>

    </div>

    <div class="navbar-right">

        {{-- Menu cloche : badge et liste mis à jour en direct (flux interrogé régulièrement, voir initNotifications) --}}
        <div class="dropdown" data-notifications data-feed-url="{{ route('admin.notifications.feed') }}">
            <button type="button" class="nav-icon" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Notifications" data-notifications-toggle>
                <i class="fa-regular fa-bell"></i>
                <span class="nav-badge" data-notifications-badge @if ($notifications === 0) hidden @endif>{{ $notifications > 9 ? '9+' : $notifications }}</span>
            </button>

            <div class="dropdown-menu dropdown-menu-end admin-dropdown notification-menu">
                <div class="admin-dropdown-header">
                    <strong>Notifications</strong>
                    <form method="POST" action="{{ route('admin.notifications.read-all') }}" data-notifications-read-all @if ($notifications === 0) hidden @endif>
                        @csrf
                        <button type="submit" class="dropdown-link-btn">Tout marquer comme lu</button>
                    </form>
                </div>

                <div class="notification-list" data-notifications-list>
                    @include('admin.partials.notification-items', [
                        'items' => $user->unreadNotifications()->latest()->limit(App\Http\Controllers\Admin\NotificationController::MENU_LIMIT)->get(),
                    ])
                </div>

                <a href="{{ route('admin.notifications.index') }}" class="admin-dropdown-footer">
                    Voir toutes les notifications <i class="fa-solid fa-arrow-right"></i>
                </a>
            </div>
        </div>

        <div class="dropdown">
            <button type="button" class="user-profile" data-bs-toggle="dropdown" aria-expanded="false">
                <img src="{{ $photo }}" alt="">

                <span class="user-info">
                    <strong>{{ $user->name }}</strong>
                    <span>{{ $user->role->label() }}</span>
                </span>

                <i class="fa-solid fa-chevron-down"></i>
            </button>

            <ul class="dropdown-menu dropdown-menu-end admin-dropdown user-menu">
                <li class="user-menu-header">
                    <img src="{{ $photo }}" alt="">
                    <span>
                        <strong>{{ $user->name }}</strong>
                        <small>{{ $user->email }}</small>
                    </span>
                </li>
                <li>
                    <hr class="dropdown-divider">
                </li>
                <li>
                    <a class="dropdown-item" href="{{ route('admin.profil.edit') }}">
                        <i class="fa-solid fa-user"></i> Mon profil
                    </a>
                </li>
                <li>
                    <a class="dropdown-item" href="{{ route('home') }}" target="_blank" rel="noopener">
                        <i class="fa-solid fa-globe"></i> Voir le site
                    </a>
                </li>
                <li>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="dropdown-item text-danger">
                            <i class="fa-solid fa-arrow-right-from-bracket"></i> Déconnexion
                        </button>
                    </form>
                </li>
            </ul>
        </div>

    </div>

</header>
