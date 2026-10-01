@php
    $slides = [
        [
            'image' => 'assets/images/home/slide-1.webp',
            'eyebrow' => 'Résidences meublées haut standing',
            'title' => 'Trouvez votre',
            'highlight' => 'résidence idéale',
            'text' => 'Séjournez avec confort, sécurité et élégance dans nos résidences sélectionnées à Abidjan et partout en Côte d’Ivoire.',
            'link' => route('residences.index'),
            'cta' => 'Découvrir nos résidences',
        ],
        [
            'image' => 'assets/images/home/slide-2.webp',
            'eyebrow' => 'Complexes avec piscine',
            'title' => 'Le confort d’un hôtel,',
            'highlight' => 'la liberté d’un chez-soi',
            'text' => 'Appartements équipés, services inclus et espaces de détente pour des séjours courts ou prolongés.',
            'link' => route('residences.index'),
            'cta' => 'Voir les disponibilités',
        ],
        [
            'image' => 'assets/images/home/slide-3.webp',
            'eyebrow' => 'Villas d’exception',
            'title' => 'Des villas pensées pour',
            'highlight' => 'vos plus beaux séjours',
            'text' => 'Piscine privée, jardin tropical et prestations sur mesure : l’adresse idéale en famille ou entre amis.',
            'link' => ($featured = $residences->first()) ? route('residences.show', $featured) : route('residences.index'),
            'cta' => $featured ? 'Découvrir notre coup de cœur' : 'Voir les résidences',
        ],
    ];
@endphp

<section class="home-hero" id="accueil" aria-roledescription="carrousel" aria-label="Nos résidences à la une" data-hero-slider>

    <div class="home-hero-slides">
        @foreach ($slides as $index => $slide)
            <article class="home-hero-slide {{ $index === 0 ? 'is-active' : '' }}"
                aria-roledescription="diapositive"
                aria-label="{{ $index + 1 }} sur {{ count($slides) }}"
                @if ($index !== 0) aria-hidden="true" @endif>

                <div class="home-hero-bg">
                    <img src="{{ asset($slide['image']) }}" alt=""
                        @if ($index === 0) fetchpriority="high" @else loading="lazy" @endif>
                </div>

                <div class="container home-hero-content">
                    <span class="home-eyebrow home-hero-anim">
                        <span class="home-eyebrow-dot"></span>
                        {{ $slide['eyebrow'] }}
                    </span>

                    {{-- Un seul h1 par page : les diapositives suivantes utilisent un h2 --}}
                    @php $tag = $index === 0 ? 'h1' : 'h2'; @endphp
                    <{{ $tag }} class="home-hero-title home-hero-anim">
                        {{ $slide['title'] }}
                        <span>{{ $slide['highlight'] }}</span>
                    </{{ $tag }}>

                    <p class="home-hero-text home-hero-anim">{{ $slide['text'] }}</p>

                    <div class="home-hero-actions home-hero-anim">
                        <a href="{{ route('residences.index') }}" class="site-btn site-btn-gold home-btn-lg">
                            Réserver maintenant
                            <i class="fa-solid fa-arrow-right"></i>
                        </a>

                        <a href="{{ $slide['link'] }}" class="site-btn site-btn-ghost home-btn-lg">
                            {{ $slide['cta'] }}
                        </a>
                    </div>
                </div>
            </article>
        @endforeach
    </div>

    {{-- Commandes du diaporama --}}
    <div class="container home-hero-controls">
        <div class="home-hero-counter" aria-hidden="true">
            <strong data-hero-current>01</strong>
            <span>/ {{ str_pad((string) count($slides), 2, '0', STR_PAD_LEFT) }}</span>
        </div>

        <div class="home-hero-dots" role="tablist" aria-label="Choisir une diapositive">
            @foreach ($slides as $index => $slide)
                <button type="button" class="home-hero-dot {{ $index === 0 ? 'is-active' : '' }}" role="tab"
                    aria-label="Diapositive {{ $index + 1 }} : {{ $slide['eyebrow'] }}"
                    aria-selected="{{ $index === 0 ? 'true' : 'false' }}" data-hero-dot="{{ $index }}">
                    <span class="home-hero-dot-bar"></span>
                </button>
            @endforeach
        </div>

        <div class="home-hero-arrows">
            <button type="button" class="home-hero-arrow" data-hero-prev aria-label="Diapositive précédente">
                <i class="fa-solid fa-arrow-left"></i>
            </button>
            <button type="button" class="home-hero-arrow" data-hero-next aria-label="Diapositive suivante">
                <i class="fa-solid fa-arrow-right"></i>
            </button>
        </div>
    </div>

</section>

{{-- Recherche : chevauche le bas du hero sur grand écran --}}
<section class="home-search" aria-label="Rechercher une résidence">
    <div class="container">
        <form class="home-search-card" action="{{ route('residences.index') }}" method="GET" data-reveal>

            <div class="home-search-field home-search-field-wide">
                <label for="search-destination"><i class="fa-solid fa-location-dot"></i> Destination</label>
                <input type="text" id="search-destination" name="destination" placeholder="Ville, quartier, résidence">
            </div>

            <div class="home-search-field">
                <label for="checkin"><i class="fa-regular fa-calendar"></i> Arrivée</label>
                <input type="date" id="checkin" name="arrivee" min="{{ now()->toDateString() }}" data-placeholder="Ajouter une date" data-datepicker data-datepicker-start="recherche">
            </div>

            <div class="home-search-field">
                <label for="checkout"><i class="fa-regular fa-calendar-check"></i> Départ</label>
                <input type="date" id="checkout" name="depart" min="{{ now()->addDay()->toDateString() }}" data-placeholder="Ajouter une date" data-datepicker data-datepicker-end="recherche">
            </div>

            <div class="home-search-field">
                <label for="search-guests"><i class="fa-solid fa-user-group"></i> Voyageurs</label>
                <select id="search-guests" name="voyageurs">
                    <option value="1">1 personne</option>
                    <option value="2" selected>2 personnes</option>
                    <option value="3">3 personnes</option>
                    <option value="4">4 personnes</option>
                    <option value="5">5 personnes et plus</option>
                </select>
            </div>

            <button type="submit" class="home-search-btn">
                <i class="fa-solid fa-magnifying-glass"></i>
                <span>Rechercher</span>
            </button>

        </form>
    </div>
</section>
