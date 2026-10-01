@php
    // Coordonnées réglées dans Administration > Paramètres du site ; une ligne vide n'est pas affichée
    $contacts = array_values(array_filter([
        ['fa-solid fa-location-dot', 'Adresse', $site->get('contact_address'), null],
        $site->phone() ? ['fa-solid fa-phone', 'Téléphone', $site->phone(), $site->phoneHref()] : null,
        ['fa-regular fa-envelope', 'Email', $site->email(), 'mailto:'.$site->email()],
        $site->whatsappUrl() ? ['fa-brands fa-whatsapp', 'WhatsApp', 'Écrivez-nous sur WhatsApp', $site->whatsappUrl()] : null,
        $site->get('contact_hours') ? ['fa-regular fa-clock', 'Horaires', $site->get('contact_hours'), null] : null,
    ]));
    $heading = $site->content('home_contact');
@endphp

<section class="home-section home-contact" id="contact">
    <div class="container">

        <div class="home-contact-layout">

            <div data-reveal>
                @if ($heading['kicker'])
                    <span class="home-kicker">{{ $heading['kicker'] }}</span>
                @endif
                <h2 class="home-title">{{ $heading['title'] }} @if ($heading['highlight'])<span>{{ $heading['highlight'] }}</span>@endif</h2>
                @if ($heading['lead'])
                    <p class="home-lead">{{ $heading['lead'] }}</p>
                @endif

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
