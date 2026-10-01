@php $newsletterErrors = $errors->getBag('newsletter'); @endphp

<footer class="footer">

    <div class="container">

        <div class="footer-top">

            {{-- Présentation --}}
            <div class="footer-col">
                <img src="{{ asset('assets/images/logo/ds_holding_logo.png') }}" alt="DS HOLDING" class="footer-logo">

                <p>
                    DS HOLDING vous propose des résidences meublées
                    haut standing alliant confort, sécurité
                    et élégance.
                </p>
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
                        Cocody, Abidjan
                    </li>
                    <li>
                        <i class="fa-solid fa-phone"></i>
                        <a href="tel:+2250141601278">+225 01 41 60 12 78</a>
                    </li>
                    <li>
                        <i class="fa-solid fa-envelope"></i>
                        <a href="mailto:contact@dsholding.ci">contact@dsholding.ci</a>
                    </li>
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
                DS HOLDING • Tous droits réservés.
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
