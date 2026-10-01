@php $newsletterErrors = $errors->getBag('newsletter'); @endphp

<footer class="footer">

    <div class="container">

        <div class="footer-top">

            {{-- Présentation --}}
            <div class="footer-col">
                <img src="{{ asset('assets/images/logo/ds_holding_logo.png') }}" alt="{{ $site->name() }}" class="footer-logo">

                <p>{{ $site->get('site_about') }}</p>

                @if ($site->socials() !== [])
                    <div class="social-links">
                        @foreach ($site->socials() as $social)
                            <a href="{{ $social['url'] }}" target="_blank" rel="noopener" aria-label="{{ $site->name() }} sur {{ $social['label'] }}">
                                <i class="fa-brands {{ $social['icon'] }}"></i>
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- Navigation --}}
            <div class="footer-col">
                <h4>Navigation</h4>

                <ul>
                    <li><a href="{{ route('home') }}">Accueil</a></li>
                    <li><a href="{{ route('residences.index') }}">Résidences</a></li>
                    <li><a href="{{ route('pages.faq') }}">Questions fréquentes</a></li>
                    <li><a href="{{ route('pages.owners') }}">Propriétaires</a></li>
                    <li><a href="{{ route('pages.contact') }}">Contact</a></li>
                </ul>
            </div>

            {{-- Contact --}}
            <div class="footer-col">
                <h4>Contact</h4>

                <ul>
                    <li>
                        <i class="fa-solid fa-location-dot"></i>
                        {{ $site->get('contact_address') }}
                    </li>
                    @if ($site->phone())
                        <li>
                            <i class="fa-solid fa-phone"></i>
                            <a href="{{ $site->phoneHref() }}">{{ $site->phone() }}</a>
                        </li>
                    @endif
                    <li>
                        <i class="fa-solid fa-envelope"></i>
                        <a href="mailto:{{ $site->email() }}">{{ $site->email() }}</a>
                    </li>
                    @if ($site->get('contact_hours'))
                        <li>
                            <i class="fa-regular fa-clock"></i>
                            {{ $site->get('contact_hours') }}
                        </li>
                    @endif
                </ul>
            </div>

            {{-- Lettre d'information --}}
            <div class="footer-col" id="newsletter">
                <h4>Newsletter</h4>

                <p>Recevez nos nouvelles résidences et nos offres.</p>

                @if (session('newsletter_sent'))
                    <p class="newsletter-feedback is-success" role="status"><i class="fa-solid fa-circle-check"></i> Merci, votre inscription est enregistrée.</p>
                @endif

                <form method="POST" action="{{ route('newsletter.store') }}" class="newsletter-form" novalidate>
                    @csrf
                    <label for="newsletter-email" class="visually-hidden">Votre email</label>
                    <input type="email" id="newsletter-email" name="email_newsletter" value="{{ old('email_newsletter') }}" placeholder="Votre email" autocomplete="email" required>
                    <button type="submit" aria-label="S’inscrire à la newsletter">
                        <i class="fa-solid fa-paper-plane"></i>
                    </button>
                </form>

                @if ($newsletterErrors->any())
                    <p class="newsletter-feedback is-error" role="alert">{{ $newsletterErrors->first() }}</p>
                @endif
            </div>

        </div>

        <div class="footer-bottom">
            <p>
                © {{ now()->year }}
                {{ $site->name() }} • Tous droits réservés.
            </p>

            <nav class="footer-legal" aria-label="Informations légales">
                <a href="{{ route('pages.conditions') }}">Conditions d’utilisation</a>
                <a href="{{ route('pages.privacy') }}">Confidentialité</a>
            </nav>
        </div>

    </div>

</footer>

<button id="backToTop" aria-label="Revenir en haut de la page">
    <i class="fa-solid fa-arrow-up"></i>
</button>
