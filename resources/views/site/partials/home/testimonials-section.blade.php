{{-- Avis vérifiés laissés par les voyageurs après leur séjour (HomeController) ; titres réglés dans Paramètres du site --}}
@php $heading = $site->content('home_reviews'); @endphp
<section class="home-section home-testimonials" id="avis">
    <div class="container">

        <div class="home-testimonials-layout">

            <div class="home-testimonials-intro" data-reveal>
                @if ($heading['kicker'])
                    <span class="home-kicker">{{ $heading['kicker'] }}</span>
                @endif
                <h2 class="home-title">{{ $heading['title'] }} @if ($heading['highlight'])<span>{{ $heading['highlight'] }}</span>@endif</h2>
                @if ($heading['lead'])
                    <p class="home-lead">{{ $heading['lead'] }}</p>
                @endif

                @if ($stats['rating'] !== null)
                    <div class="home-rating-summary">
                        <strong>{{ number_format($stats['rating'], 1, ',', ' ') }}</strong>
                        <div>
                            <span class="home-stars" aria-label="Note de {{ number_format($stats['rating'], 1, ',', ' ') }} sur 10">
                                @for ($i = 1; $i <= 5; $i++)
                                    <i class="fa-{{ $stats['rating'] / 2 >= $i - 0.25 ? 'solid' : 'regular' }} fa-star"></i>
                                @endfor
                            </span>
                            <span>Note moyenne sur 10 · {{ $stats['reviews'] }} avis</span>
                        </div>
                    </div>
                @endif
            </div>

            <div class="home-testimonials-track" tabindex="0" aria-label="Avis de voyageurs">
                @forelse ($testimonials as $index => $review)
                    @php
                        $author = $review->authorName();
                    @endphp
                    <figure class="home-testimonial" data-reveal style="--reveal-delay: {{ ($index % 3) * 120 }}ms">
                        <i class="fa-solid fa-quote-left home-testimonial-quote"></i>

                        <span class="home-stars" aria-label="Note de {{ $review->rating }} sur 10">
                            @for ($i = 1; $i <= 5; $i++)
                                <i class="fa-{{ $review->rating / 2 >= $i - 0.25 ? 'solid' : 'regular' }} fa-star"></i>
                            @endfor
                        </span>

                        <blockquote>{{ Str::limit($review->comment, 220) }}</blockquote>

                        <figcaption>
                            <img src="{{ $review->user?->avatarUrl() ?? asset('assets/images/testimonials/client-1.png') }}" alt="" loading="lazy">
                            <span>
                                <strong>{{ $author }}</strong>
                                <small>
                                    <a href="{{ route('residences.show', $review->property) }}">{{ $review->property->name }}</a>
                                    · {{ $review->created_at->translatedFormat('F Y') }}
                                </small>
                            </span>
                        </figcaption>
                    </figure>
                @empty
                    <figure class="home-testimonial home-testimonial-empty" data-reveal>
                        <i class="fa-solid fa-quote-left home-testimonial-quote"></i>
                        <blockquote>Les premiers avis de nos voyageurs apparaîtront ici après leurs séjours.</blockquote>
                        <figcaption>
                            <a href="{{ route('residences.index') }}" class="site-btn site-btn-gold">Trouver une résidence <i class="fa-solid fa-arrow-right"></i></a>
                        </figcaption>
                    </figure>
                @endforelse
            </div>

        </div>

    </div>
</section>
