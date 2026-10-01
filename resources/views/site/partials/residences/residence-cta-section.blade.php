@php
    // Contenu réglé dans Paramètres du site > Contenu des pages > Résidences
    $cta = $site->content('listing_cta');
@endphp

<section class="listing-cta">
    <div class="container">
        <div class="listing-cta-card">
            <span class="listing-cta-icon"><i class="fa-solid fa-building-circle-check"></i></span>

            <div class="listing-cta-content">
                <h2>{{ $cta['title'] }}</h2>
                @if ($cta['text'])
                    <p>{{ $cta['text'] }}</p>
                @endif
            </div>

            <a href="{{ $site->link($cta['button_link']) }}" class="site-btn site-btn-gold listing-cta-btn">
                {{ $cta['button_label'] }}
                <i class="fa-solid fa-arrow-right"></i>
            </a>
        </div>
    </div>
</section>
