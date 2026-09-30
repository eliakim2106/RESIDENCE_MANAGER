@php
    $residences = [
        [
            'image' => 'assets/images/home/residence-1.webp',
            'badge' => 'Coup de cœur',
            'name' => 'DS Palace',
            'location' => 'Cocody, Abidjan',
            'type' => 'Villa · 4 voyageurs',
            'rating' => '4,8',
            'reviews' => 124,
            'features' => [['fa-wifi', 'Wi-Fi'], ['fa-water-ladder', 'Piscine'], ['fa-snowflake', 'Clim.']],
            'price' => '35 000',
        ],
        [
            'image' => 'assets/images/home/residence-2.webp',
            'badge' => 'Populaire',
            'name' => 'Résidence Les Cocotiers',
            'location' => 'Riviera 3, Abidjan',
            'type' => 'Appartement · 3 voyageurs',
            'rating' => '4,7',
            'reviews' => 86,
            'features' => [['fa-wifi', 'Wi-Fi'], ['fa-car', 'Parking'], ['fa-dumbbell', 'Salle de sport']],
            'price' => '28 000',
        ],
        [
            'image' => 'assets/images/home/residence-3.webp',
            'badge' => 'Nouveau',
            'name' => 'Villa Océane',
            'location' => 'Assinie-Mafia',
            'type' => 'Villa · 8 voyageurs',
            'rating' => '4,9',
            'reviews' => 42,
            'features' => [['fa-umbrella-beach', 'Plage'], ['fa-water-ladder', 'Piscine'], ['fa-utensils', 'Cuisine']],
            'price' => '85 000',
        ],
    ];
@endphp

<section class="home-section" id="residences">
    <div class="container">

        <div class="home-section-head home-section-head-split" data-reveal>
            <div>
                <span class="home-kicker">Sélection DS HOLDING</span>
                <h2 class="home-title">Nos résidences <span>les plus demandées</span></h2>
            </div>

            <a href="{{ route('residences.index') }}" class="home-link-arrow">
                Toutes les résidences
                <i class="fa-solid fa-arrow-right"></i>
            </a>
        </div>

        <div class="home-residences-grid">
            @foreach ($residences as $index => $residence)
                <article class="home-residence" data-reveal style="--reveal-delay: {{ $index * 120 }}ms">

                    <a href="{{ route('residences.show') }}" class="home-residence-media" aria-label="Voir {{ $residence['name'] }}">
                        <img src="{{ asset($residence['image']) }}" alt="{{ $residence['name'] }}" loading="lazy">
                        <span class="home-residence-badge">{{ $residence['badge'] }}</span>
                    </a>

                    <button type="button" class="home-residence-fav" aria-label="Ajouter {{ $residence['name'] }} aux favoris" aria-pressed="false" data-favorite>
                        <i class="fa-regular fa-heart"></i>
                    </button>

                    <div class="home-residence-body">
                        <div class="home-residence-meta">
                            <span><i class="fa-solid fa-location-dot"></i> {{ $residence['location'] }}</span>
                            <span class="home-residence-rating">
                                <i class="fa-solid fa-star"></i>
                                {{ $residence['rating'] }}
                                <small>({{ $residence['reviews'] }})</small>
                            </span>
                        </div>

                        <h3>
                            <a href="{{ route('residences.show') }}">{{ $residence['name'] }}</a>
                        </h3>

                        <p class="home-residence-type">{{ $residence['type'] }}</p>

                        <ul class="home-residence-features">
                            @foreach ($residence['features'] as [$icon, $label])
                                <li><i class="fa-solid {{ $icon }}"></i> {{ $label }}</li>
                            @endforeach
                        </ul>

                        <div class="home-residence-footer">
                            <p class="home-residence-price">
                                <strong>{{ $residence['price'] }} FCFA</strong>
                                <span>/ nuit</span>
                            </p>

                            <a href="{{ route('residences.show') }}" class="home-residence-cta" aria-label="Réserver {{ $residence['name'] }}">
                                <i class="fa-solid fa-arrow-right"></i>
                            </a>
                        </div>
                    </div>

                </article>
            @endforeach
        </div>

    </div>
</section>
