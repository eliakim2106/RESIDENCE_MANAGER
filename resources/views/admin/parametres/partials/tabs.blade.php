{{-- Onglets du module Paramètres du site --}}
<nav class="settings-tabs" aria-label="Paramètres du site">
    <a href="{{ route('admin.parametres.edit') }}" class="settings-tab {{ request()->routeIs('admin.parametres.edit') ? 'is-active' : '' }}"
        @if (request()->routeIs('admin.parametres.edit')) aria-current="page" @endif>
        <i class="fa-solid fa-sliders"></i>
        Général
        <small>Identité, coordonnées, réseaux, réservation</small>
    </a>
    <a href="{{ route('admin.parametres.contenu', 'accueil') }}" class="settings-tab {{ request()->routeIs('admin.parametres.contenu*') ? 'is-active' : '' }}"
        @if (request()->routeIs('admin.parametres.contenu*')) aria-current="page" @endif>
        <i class="fa-solid fa-pen-ruler"></i>
        Contenu des pages
        <small>Diaporama, sections, en-têtes, questions fréquentes</small>
    </a>
</nav>
