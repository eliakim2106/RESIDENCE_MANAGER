@php
    $user = auth()->user();
    $photo = $user->avatar_path
        ? \Illuminate\Support\Facades\Storage::disk('public')->url($user->avatar_path)
        : 'https://ui-avatars.com/api/?background=005bff&color=fff&name='.urlencode($user->name);
@endphp

<nav class="navbar">

    <div class="navbar-left">

        <button type="button" class="menu-toggle">
            <i class="fa-solid fa-bars"></i>
        </button>

        <div class="welcome-box">
            <h3>Bonjour 👋, {{ $user->name }}</h3>
            <span>Bon retour sur DS HOLDING</span>
        </div>

    </div>

    <div class="navbar-right">

        <div class="nav-icon notification">
            <i class="fa-regular fa-bell"></i>
            <span class="badge">{{ $user->unreadNotifications()->count() }}</span>
        </div>

        <div class="nav-icon notification">
            <i class="fa-regular fa-envelope"></i>
            <span class="badge">0</span>
        </div>

        <div class="user-profile">
            <img src="{{ $photo }}" alt="{{ $user->name }}">

            <div class="user-info">
                <strong>{{ $user->name }}</strong>
                <span>{{ $user->role->label() }}</span>
            </div>

            <i class="fa-solid fa-chevron-down"></i>
        </div>

    </div>

</nav>
