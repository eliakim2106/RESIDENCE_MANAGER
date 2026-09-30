@php
    $services = [
        ['fa-shield-halved', 'Sécurité 24h/24', 'Gardiennage, vidéosurveillance et accès contrôlé dans chaque résidence.'],
        ['fa-wifi', 'Wi-Fi haut débit', 'Une connexion fiable pour travailler ou vous divertir sans interruption.'],
        ['fa-water-ladder', 'Piscine & détente', 'Espaces de repos, piscines et jardins pour vous ressourcer.'],
        ['fa-car', 'Parking privé', 'Places sécurisées réservées aux résidents, sans supplément.'],
        ['fa-broom', 'Ménage & conciergerie', 'Linge fourni, ménage régulier et conciergerie à votre écoute.'],
        ['fa-headset', 'Assistance 24h/24', 'Une équipe joignable à toute heure par téléphone ou WhatsApp.'],
    ];
@endphp

<section class="home-section home-services" id="services">
    <div class="container">

        <div class="home-section-head" data-reveal>
            <span class="home-kicker home-kicker-light">Pourquoi DS HOLDING</span>
            <h2 class="home-title home-title-light">Tout est prévu pour <span>un séjour sans souci</span></h2>
            <p class="home-lead home-lead-light">
                Des résidences modernes et sécurisées, et une équipe qui s’occupe de chaque détail
                pour rendre votre séjour exceptionnel.
            </p>
        </div>

        <div class="home-bento">

            <div class="home-bento-feature" data-reveal>
                <img src="{{ asset('assets/images/home/interieur.webp') }}" alt="Salon d’une résidence DS HOLDING" loading="lazy">
                <div class="home-bento-feature-content">
                    <span class="home-bento-tag"><i class="fa-solid fa-couch"></i> Intérieurs soignés</span>
                    <h3>Des logements entièrement équipés, prêts à vivre</h3>
                    <p>Cuisine équipée, literie de qualité et climatisation pour vous sentir comme chez vous.</p>
                </div>
            </div>

            @foreach ($services as $index => [$icon, $title, $text])
                <div class="home-bento-card" data-reveal style="--reveal-delay: {{ ($index + 1) * 80 }}ms">
                    <span class="home-bento-icon"><i class="fa-solid {{ $icon }}"></i></span>
                    <h3>{{ $title }}</h3>
                    <p>{{ $text }}</p>
                </div>
            @endforeach

        </div>

    </div>
</section>
