@php
    $user = auth()->user();
    $photo = $user->avatarUrl();
    $notifications = $user->unreadNotifications()->count();

    // Fil d'Ariane : section d'après le nom de la route, puis la page en cours (section « title »)
    $sections = [
        'admin.reservations.' => [$user->isAdmin() || $user->isOwner() ? 'Réservations' : 'Mes réservations', 'admin.reservations.index'],
        'admin.paiements.' => ['Paiements', 'admin.paiements.index'],
        'admin.reversements.' => [$user->isAdmin() ? 'Reversements' : 'Mes reversements', $user->isAdmin() ? 'admin.reversements.index' : 'admin.mes-reversements.index'],
        'admin.mes-reversements.' => ['Mes reversements', 'admin.mes-reversements.index'],
        'admin.validations.' => ['Validations', 'admin.validations.index'],
        'admin.utilisateurs.' => ['Utilisateurs', 'admin.utilisateurs.index'],
        'admin.abonnements.' => ['Abonnements', 'admin.abonnements.index'],
        'admin.formules.' => ['Formules', 'admin.formules.index'],
        'admin.abonnement.' => ['Mon abonnement', 'admin.abonnement.show'],
        'admin.profil.' => ['Mon profil', 'admin.profil.edit'],
        'admin.etablissements.unites.' => ['Établissements', 'admin.etablissements.index'],
        'admin.etablissements.' => ['Établissements', 'admin.etablissements.index'],
        'admin.unites.' => ['Unités', 'admin.unites.index'],
        'admin.types-etablissement.' => ["Types d'établissement", 'admin.types-etablissement.index'],
        'admin.types-unite.' => ["Types d'unité", 'admin.types-unite.index'],
        'admin.equipements.' => ['Équipements', 'admin.equipements.index'],
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

        <a href="{{ route('home') }}" class="nav-icon" target="_blank" rel="noopener" title="Voir le site" aria-label="Voir le site dans un nouvel onglet">
            <i class="fa-solid fa-arrow-up-right-from-square"></i>
        </a>

        <div class="dropdown">
            <button type="button" class="nav-icon" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Notifications">
                <i class="fa-regular fa-bell"></i>
                @if ($notifications > 0)
                    <span class="nav-badge">{{ $notifications > 9 ? '9+' : $notifications }}</span>
                @endif
            </button>

            <div class="dropdown-menu dropdown-menu-end admin-dropdown">
                <div class="admin-dropdown-header">
                    <strong>Notifications</strong>
                    @if ($notifications > 0)
                        <form method="POST" action="{{ route('admin.notifications.read-all') }}">
                            @csrf
                            <button type="submit" class="dropdown-link-btn">Tout marquer comme lu</button>
                        </form>
                    @endif
                </div>
                @forelse ($user->unreadNotifications()->latest()->limit(5)->get() as $notification)
                    <a href="{{ route('admin.notifications.open', $notification->id) }}" class="admin-dropdown-item notification-item">
                        <i class="fa-solid {{ $notification->data['icon'] ?? 'fa-bell' }} tone-{{ $notification->data['tone'] ?? 'info' }}"></i>
                        <span>
                            {{ $notification->data['message'] ?? 'Nouvelle notification' }}
                            <small>{{ $notification->created_at->diffForHumans() }}</small>
                        </span>
                    </a>
                @empty
                    <div class="admin-dropdown-empty">
                        <i class="fa-regular fa-bell-slash"></i>
                        <span>Aucune nouvelle notification</span>
                    </div>
                @endforelse
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
                <li><hr class="dropdown-divider"></li>
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
