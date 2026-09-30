@php
    $contacts = [
        ['fa-solid fa-location-dot', 'Adresse', 'Cocody, Abidjan', null],
        ['fa-solid fa-phone', 'Téléphone', '+225 01 41 60 12 78', 'tel:+2250141601278'],
        ['fa-regular fa-envelope', 'Email', 'contact@dsholding.ci', 'mailto:contact@dsholding.ci'],
        ['fa-brands fa-whatsapp', 'WhatsApp', 'Réponse rapide 24h/24', 'https://wa.me/2250141601278'],
    ];
@endphp

<section class="home-section home-contact" id="contact">
    <div class="container">

        <div class="home-contact-layout">

            <div data-reveal>
                <span class="home-kicker">Contact</span>
                <h2 class="home-title">Besoin <span>d’informations ?</span></h2>
                <p class="home-lead">Notre équipe vous répond rapidement pour toute question sur une résidence ou une réservation.</p>

                <ul class="home-contact-list">
                    @foreach ($contacts as [$icon, $label, $value, $href])
                        <li>
                            <span class="home-contact-icon"><i class="{{ $icon }}"></i></span>
                            <span>
                                <small>{{ $label }}</small>
                                @if ($href)
                                    <a href="{{ $href }}" @if (str_starts_with($href, 'http')) target="_blank" rel="noopener" @endif>{{ $value }}</a>
                                @else
                                    <strong>{{ $value }}</strong>
                                @endif
                            </span>
                        </li>
                    @endforeach
                </ul>
            </div>

            <form class="home-contact-form" data-reveal style="--reveal-delay: 120ms">
                <h3>Envoyez-nous un message</h3>

                <div class="home-form-row">
                    <div class="home-field">
                        <label for="contact-name">Nom complet</label>
                        <input type="text" id="contact-name" name="nom" placeholder="Votre nom" autocomplete="name">
                    </div>

                    <div class="home-field">
                        <label for="contact-email">Email</label>
                        <input type="email" id="contact-email" name="email" placeholder="vous@exemple.com" autocomplete="email">
                    </div>
                </div>

                <div class="home-field">
                    <label for="contact-subject">Sujet</label>
                    <input type="text" id="contact-subject" name="sujet" placeholder="Réservation, information, partenariat…">
                </div>

                <div class="home-field">
                    <label for="contact-message">Message</label>
                    <textarea id="contact-message" name="message" rows="5" placeholder="Votre message"></textarea>
                </div>

                <button type="submit" class="site-btn site-btn-gold home-btn-lg">
                    Envoyer le message
                    <i class="fa-solid fa-paper-plane"></i>
                </button>
            </form>

        </div>

    </div>
</section>
