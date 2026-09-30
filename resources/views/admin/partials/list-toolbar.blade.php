{{--
    Barre d'outils des listes : onglets de statut avec compteurs, puis recherche.
    Paramètres : $counts, $statut, $search, $placeholder,
    $labels (libellés à remplacer, facultatif) ou $tabs (liste complète des onglets, facultatif ; le premier est l'onglet par défaut)
    La recherche conserve les autres filtres de l'adresse (établissement, moyen de paiement…).
--}}
@php
    // Mêmes clés que FiltersByStatus::STATUS_TABS (une constante de trait ne se lit pas depuis une vue)
    $labels = $tabs ?? array_merge(['tous' => 'Tous', 'actifs' => 'Actifs', 'inactifs' => 'Inactifs'], $labels ?? []);
    $defaultTab = array_key_first($labels);
@endphp

<div class="list-toolbar">
    <nav class="status-tabs" aria-label="Filtrer par statut">
        @foreach (array_keys($labels) as $key)
            <a href="{{ request()->fullUrlWithQuery(['statut' => $key === $defaultTab ? null : $key, 'page' => null]) }}"
                class="status-tab {{ $statut === $key ? 'is-active' : '' }}"
                @if ($statut === $key) aria-current="page" @endif>
                {{ $labels[$key] }}
                <span>{{ $counts[$key] }}</span>
            </a>
        @endforeach
    </nav>

    <form method="GET" class="search-box" role="search">
        @foreach (request()->except(['search', 'page']) as $name => $value)
            @if (is_string($value) && $value !== '')
                <input type="hidden" name="{{ $name }}" value="{{ $value }}">
            @endif
        @endforeach
        <i class="fa-solid fa-magnifying-glass"></i>
        <input type="search" name="search" value="{{ $search }}" placeholder="{{ $placeholder }}" aria-label="{{ $placeholder }}">
        @if ($search !== '')
            <a href="{{ request()->fullUrlWithQuery(['search' => null, 'page' => null]) }}" class="search-clear" aria-label="Effacer la recherche">
                <i class="fa-solid fa-xmark"></i>
            </a>
        @endif
    </form>
</div>
