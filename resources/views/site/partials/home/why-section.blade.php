@php
    // Contenu réglé dans Paramètres du site > Contenu des pages > Accueil
    $why = $site->content('home_why');
    $services = $why['items'];
    // La grande image occupe autant de rangées que les cartes (deux par rangée)
    $rows = max(1, (int) ceil(count($services) / 2));
@endphp

<section class="home-section home-services" id="services">
    <div class="container">

        <div class="home-section-head" data-reveal>
            @if ($why['kicker'])
                <span class="home-kicker home-kicker-light">{{ $why['kicker'] }}</span>
            @endif
            <h2 class="home-title home-title-light">{{ $why['title'] }} @if ($why['highlight'])<span>{{ $why['highlight'] }}</span>@endif</h2>
            @if ($why['lead'])
                <p class="home-lead home-lead-light">{{ $why['lead'] }}</p>
            @endif
        </div>

        <div class="home-bento" style="--bento-rows: {{ $rows }}">

            <div class="home-bento-feature" data-reveal>
                <img src="{{ $site->image($why['image'], 'assets/images/home/interieur.webp') }}" alt="{{ $why['image_title'] }}" loading="lazy">
                <div class="home-bento-feature-content">
                    @if ($why['image_tag'])
                        <span class="home-bento-tag"><i class="fa-solid fa-couch"></i> {{ $why['image_tag'] }}</span>
                    @endif
                    @if ($why['image_title'])
                        <h3>{{ $why['image_title'] }}</h3>
                    @endif
                    @if ($why['image_text'])
                        <p>{{ $why['image_text'] }}</p>
                    @endif
                </div>
            </div>

            @foreach ($services as $index => $service)
                <div class="home-bento-card" data-reveal style="--reveal-delay: {{ ($index + 1) * 80 }}ms">
                    <span class="home-bento-icon"><i class="fa-solid {{ $service['icon'] }}"></i></span>
                    <h3>{{ $service['title'] }}</h3>
                    @if ($service['text'])
                        <p>{{ $service['text'] }}</p>
                    @endif
                </div>
            @endforeach

        </div>

    </div>
</section>
