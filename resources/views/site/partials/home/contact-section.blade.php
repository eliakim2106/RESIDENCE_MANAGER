@php
    $contacts = [
        ['fa-solid fa-location-dot', 'Adresse', 'Cocody, Abidjan', null],
        ['fa-solid fa-phone', 'Téléphone', '+225 01 41 60 12 78', 'tel:+2250141601278'],
        ['fa-regular fa-envelope', 'Email', 'contact@dsholding.ci', 'mailto:contact@dsholding.ci'],
        ['fa-brands fa-whatsapp', 'WhatsApp', 'Écrivez-nous sur WhatsApp', 'https://wa.me/2250141601278'],
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

            @include('site.partials.contact-form')

        </div>

    </div>
</section>
