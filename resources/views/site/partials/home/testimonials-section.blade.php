@php
    $testimonials = [
        ['client-1.png', 'Jean Koffi', 'Entrepreneur', 5, 'Séjour exceptionnel ! Résidence moderne, propre et parfaitement sécurisée. L’équipe a été aux petits soins du début à la fin.'],
        ['client-2.png', 'Marie-Camille', 'Consultante', 5, 'Une expérience incroyable. Le personnel est très professionnel et l’appartement correspondait exactement aux photos.'],
        ['client-3.png', 'Michael T.', 'Manager', 5, 'Un vrai havre de paix pour mes déplacements professionnels. Je recommande fortement DS HOLDING.'],
    ];
@endphp

<section class="home-section home-testimonials" id="avis">
    <div class="container">

        <div class="home-testimonials-layout">

            <div class="home-testimonials-intro" data-reveal>
                <span class="home-kicker">Avis clients</span>
                <h2 class="home-title">Ils ont séjourné <span>chez nous</span></h2>
                <p class="home-lead">La satisfaction de nos clients est notre plus belle récompense.</p>

                <div class="home-rating-summary">
                    <strong>4,8</strong>
                    <div>
                        <span class="home-stars" aria-label="Note de 4,8 sur 5">
                            @for ($i = 0; $i < 5; $i++)
                                <i class="fa-solid fa-star"></i>
                            @endfor
                        </span>
                        <span>Note moyenne de nos clients</span>
                    </div>
                </div>
            </div>

            <div class="home-testimonials-track" tabindex="0" aria-label="Témoignages de clients">
                @foreach ($testimonials as $index => [$photo, $name, $role, $stars, $text])
                    <figure class="home-testimonial" data-reveal style="--reveal-delay: {{ $index * 120 }}ms">
                        <i class="fa-solid fa-quote-left home-testimonial-quote"></i>

                        <span class="home-stars" aria-label="Note de {{ $stars }} sur 5">
                            @for ($i = 0; $i < $stars; $i++)
                                <i class="fa-solid fa-star"></i>
                            @endfor
                        </span>

                        <blockquote>{{ $text }}</blockquote>

                        <figcaption>
                            <img src="{{ asset('assets/images/testimonials/'.$photo) }}" alt="" loading="lazy">
                            <span>
                                <strong>{{ $name }}</strong>
                                <small>{{ $role }}</small>
                            </span>
                        </figcaption>
                    </figure>
                @endforeach
            </div>

        </div>

    </div>
</section>
