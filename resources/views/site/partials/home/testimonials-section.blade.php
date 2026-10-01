{{-- Avis vérifiés laissés par les voyageurs après leur séjour (HomeController) --}}
<section class="home-section home-testimonials" id="avis">
    <div class="container">

        <div class="home-testimonials-layout">

            <div class="home-testimonials-intro" data-reveal>
                <span class="home-kicker">Avis clients</span>
                <h2 class="home-title">Ils ont séjourné <span>chez nous</span></h2>
                <p class="home-lead">Seuls les voyageurs ayant séjourné dans une résidence peuvent la noter : chaque avis correspond à une réservation réelle.</p>

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
                        $author = $review->user ? Str::before($review->user->name, ' ').' '.Str::substr(Str::after($review->user->name, ' '), 0, 1).'.' : 'Voyageur';
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
