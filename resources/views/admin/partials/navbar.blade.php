@php
    $user = auth()->user();
    $photo = $user->avatar_path
        ? \Illuminate\Support\Facades\Storage::disk('public')->url($user->avatar_path)
        : 'https://ui-avatars.com/api/?background=1565c0&color=fff&bold=true&name='.urlencode($user->name);
    $notifications = $user->unreadNotifications()->count();
@endphp

<header class="navbar">

    <div class="navbar-left">

        <button type="button" class="menu-toggle" aria-label="Afficher ou masquer le menu" aria-controls="sidebar">
            <i class="fa-solid fa-bars"></i>
        </button>

        <div class="welcome-box">
            <h3>Bonjour, {{ Str::before($user->name, ' ') ?: $user->name }} 👋</h3>
            <span>Bon retour sur DS HOLDING</span>
        </div>

    </div>

    <div class="navbar-right">

        <button type="button" class="nav-icon" aria-label="Notifications">
            <i class="fa-regular fa-bell"></i>
            @if ($notifications > 0)
                <span class="nav-badge">{{ $notifications > 9 ? '9+' : $notifications }}</span>
            @endif
        </button>

        <div class="dropdown">
            <button type="button" class="user-profile" data-bs-toggle="dropdown" aria-expanded="false">
                <img src="{{ $photo }}" alt="">

                <span class="user-info">
                    <strong>{{ $user->name }}</strong>
                    <span>{{ $user->role->label() }}</span>
                </span>

                <i class="fa-solid fa-chevron-down"></i>
            </button>

            <ul class="dropdown-menu dropdown-menu-end user-menu">
                <li class="user-menu-header">
                    <strong>{{ $user->name }}</strong>
                    <span>{{ $user->email }}</span>
                </li>
                <li><hr class="dropdown-divider"></li>
                <li>
                    <a class="dropdown-item" href="{{ route('home') }}">
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
