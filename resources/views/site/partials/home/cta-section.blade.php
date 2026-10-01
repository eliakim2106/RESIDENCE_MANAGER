@php
    // Contenu réglé dans Paramètres du site > Contenu des pages > Accueil
    $cta = $site->content('home_cta');
@endphp

<section class="home-cta">
    <div class="container">
        <div class="home-cta-card" data-reveal>
            <img src="{{ $site->image($cta['image'], 'assets/images/home/slide-2.webp') }}" alt="" class="home-cta-bg" loading="lazy">

            <div class="home-cta-content">
                @if ($cta['kicker'])
                    <span class="home-kicker home-kicker-light">{{ $cta['kicker'] }}</span>
                @endif
                <h2>{{ $cta['title'] }}</h2>
                @if ($cta['text'])
                    <p>{{ $cta['text'] }}</p>
                @endif
            </div>

            <div class="home-cta-actions">
                <a href="{{ $site->link($cta['primary_link']) }}" class="site-btn site-btn-gold home-btn-lg">
                    {{ $cta['primary_label'] }}
                    <i class="fa-solid fa-arrow-right"></i>
                </a>
                @if ($cta['secondary_label'] && $cta['secondary_link'])
                    <a href="{{ $site->link($cta['secondary_link']) }}" class="site-btn site-btn-ghost home-btn-lg">
                        {{ $cta['secondary_label'] }}
                    </a>
                @endif
            </div>
        </div>
    </div>
</section>
