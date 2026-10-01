{{--
    En-tête du module Paramètres du site : titre, dernière modification, onglets.
    Paramètres : $lastChange (Setting|null), $previewUrl (page publique à ouvrir)
--}}
@php
    $onContent = request()->routeIs('admin.parametres.contenu*');
@endphp

<header class="prm-header">
    <div class="prm-header-main">
        <span class="prm-header-icon"><i class="fa-solid fa-sliders"></i></span>

        <div class="prm-header-text">
            <h1>Paramètres du site</h1>
            <p>
                @if ($lastChange)
                    <i class="fa-regular fa-clock"></i>
                    Dernière modification {{ $lastChange->updated_at->diffForHumans() }}
                    <span class="prm-dot">·</span>
                    {{ $lastChange->updated_at->translatedFormat('d M Y à H:i') }}
                    @if ($lastChange->updater)
                        par <strong>{{ $lastChange->updater->name }}</strong>
                    @endif
                @else
                    <i class="fa-solid fa-circle-check"></i>
                    {{ $onContent ? 'Contenu d’origine sur toutes les pages : rien n’a encore été modifié.' : 'Valeurs d’origine : rien n’a encore été modifié.' }}
                @endif
            </p>
        </div>

        <a href="{{ $previewUrl }}" target="_blank" rel="noopener" class="prm-header-link">
            <i class="fa-solid fa-arrow-up-right-from-square"></i>
            <span>Voir sur le site</span>
        </a>
    </div>

    <nav class="prm-tabs" aria-label="Paramètres du site">
        <a href="{{ route('admin.parametres.edit') }}" class="prm-tab {{ $onContent ? '' : 'is-active' }}" @unless ($onContent) aria-current="page" @endunless>
            <i class="fa-solid fa-gear"></i>
            <span>Général</span>
            <small>Identité, coordonnées, réseaux, réservation</small>
        </a>
        <a href="{{ route('admin.parametres.contenu', 'accueil') }}" class="prm-tab {{ $onContent ? 'is-active' : '' }}" @if ($onContent) aria-current="page" @endif>
            <i class="fa-solid fa-pen-ruler"></i>
            <span>Contenu des pages</span>
            <small>Diaporama, sections, en-têtes, questions</small>
        </a>
    </nav>
</header>
