{{--
    Barre d'outils des listes : onglets de statut avec compteurs, puis recherche.
    Paramètres : $counts (tous, actifs, inactifs), $statut, $search, $placeholder, $labels (libellés des onglets, facultatif)
--}}
@php
    // Mêmes clés que FiltersByStatus::STATUS_TABS (une constante de trait ne se lit pas depuis une vue)
    $labels = array_merge(['tous' => 'Tous', 'actifs' => 'Actifs', 'inactifs' => 'Inactifs'], $labels ?? []);
@endphp

<div class="list-toolbar">
    <nav class="status-tabs" aria-label="Filtrer par statut">
        @foreach (array_keys($labels) as $key)
            <a href="{{ request()->fullUrlWithQuery(['statut' => $key === 'tous' ? null : $key, 'page' => null]) }}"
                class="status-tab {{ $statut === $key ? 'is-active' : '' }}"
                @if ($statut === $key) aria-current="page" @endif>
                {{ $labels[$key] }}
                <span>{{ $counts[$key] }}</span>
            </a>
        @endforeach
    </nav>

    <form method="GET" class="search-box" role="search">
        @if ($statut !== 'tous')
            <input type="hidden" name="statut" value="{{ $statut }}">
        @endif
        <i class="fa-solid fa-magnifying-glass"></i>
        <input type="search" name="search" value="{{ $search }}" placeholder="{{ $placeholder }}" aria-label="{{ $placeholder }}">
        @if ($search !== '')
            <a href="{{ request()->fullUrlWithQuery(['search' => null, 'page' => null]) }}" class="search-clear" aria-label="Effacer la recherche">
                <i class="fa-solid fa-xmark"></i>
            </a>
        @endif
    </form>
</div>
