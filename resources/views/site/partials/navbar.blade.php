@php
    // Au-dessus d'un visuel plein écran (accueil, liste des résidences), la barre est transparente jusqu'au défilement
    $transparent = request()->routeIs('home', 'residences.index');

    $links = [
        ['Accueil', route('home'), request()->routeIs('home'), 'accueil'],
        ['Résidences', route('residences.index'), request()->routeIs('residences.*'), null],
        ['Services', route('home').'#services', false, 'services'],
        ['Avis clients', route('home').'#avis', false, 'avis'],
        ['Contact', route('home').'#contact', false, 'contact'],
    ];
@endphp

<header class="site-header {{ $transparent ? 'is-transparent' : '' }}" id="siteHeader">
    <div class="container site-header-inner">

        <a class="site-brand" href="{{ route('home') }}" aria-label="DS HOLDING, accueil">
            <img src="{{ asset('assets/images/logo/ds_holding_logo.png') }}" alt="DS HOLDING">
        </a>

        <nav class="site-nav" id="siteNav" aria-label="Navigation principale">
            <ul class="site-nav-links">
                @foreach ($links as [$label, $url, $active, $section])
                    <li>
                        <a href="{{ $url }}"
                            class="site-nav-link {{ $active ? 'is-active' : '' }}"
                            @if ($section) data-nav-section="{{ $section }}" @endif
                            @if ($active) aria-current="page" @endif>
                            {{ $label }}
                        </a>
                    </li>
                @endforeach
            </ul>

            <div class="site-nav-actions">
                @auth
                    <a href="{{ route('dashboard') }}" class="site-btn site-btn-ghost">
                        <i class="fa-regular fa-circle-user"></i>
                        Mon espace
                    </a>
                @else
                    <a href="{{ route('login') }}" class="site-btn site-btn-ghost">
                        <i class="fa-regular fa-circle-user"></i>
                        Connexion
                    </a>
                @endauth

                <a href="{{ route('residences.index') }}" class="site-btn site-btn-gold">
                    Réserver
                    <i class="fa-solid fa-arrow-right"></i>
                </a>
            </div>

            <div class="site-nav-contact">
                <a href="tel:+2250141601278"><i class="fa-solid fa-phone"></i> +225 01 41 60 12 78</a>
                <a href="mailto:contact@dsholding.ci"><i class="fa-regular fa-envelope"></i> contact@dsholding.ci</a>
            </div>
        </nav>

        <button type="button" class="site-nav-toggle" aria-controls="siteNav" aria-expanded="false" aria-label="Ouvrir le menu">
            <span></span>
            <span></span>
            <span></span>
        </button>

    </div>
</header>
