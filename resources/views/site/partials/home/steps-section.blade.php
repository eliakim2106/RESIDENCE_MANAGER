@php
    $steps = [
        ['fa-magnifying-glass-location', 'Choisissez', 'Parcourez nos résidences, comparez les équipements et trouvez celle qui vous ressemble.'],
        ['fa-calendar-check', 'Réservez', 'Sélectionnez vos dates et confirmez en quelques clics, avec un paiement sécurisé.'],
        ['fa-key', 'Profitez', 'Accueil personnalisé, remise des clés et assistance pendant tout votre séjour.'],
    ];
@endphp

<section class="home-section home-steps">
    <div class="container">

        <div class="home-section-head" data-reveal>
            <span class="home-kicker">Simple et rapide</span>
            <h2 class="home-title">Réservez en <span>trois étapes</span></h2>
        </div>

        <ol class="home-steps-list">
            @foreach ($steps as $index => [$icon, $title, $text])
                <li class="home-step" data-reveal style="--reveal-delay: {{ $index * 140 }}ms">
                    <span class="home-step-number">{{ str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) }}</span>
                    <span class="home-step-icon"><i class="fa-solid {{ $icon }}"></i></span>
                    <h3>{{ $title }}</h3>
                    <p>{{ $text }}</p>
                </li>
            @endforeach
        </ol>

    </div>
</section>
