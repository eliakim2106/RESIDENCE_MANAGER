<footer class="footer">

    <div class="container">

        <div class="footer-top">

            <!-- COLONNE 1 -->
            <div class="footer-col">

                <img
                    src="{{ asset('assets/images/logo/ds_holding_logo.png') }}"
                    alt="DS HOLDING"
                    class="footer-logo">

                <p>
                    DS HOLDING vous propose des résidences meublées
                    haut standing alliant confort, sécurité
                    et élégance.
                </p>

            </div>

            <!-- COLONNE 2 -->
            <div class="footer-col">

                <h4>Navigation</h4>

                <ul>

                    <li><a href="{{ route('home') }}">Accueil</a></li>

                    <li><a href="{{ route('residences.index') }}">Résidences</a></li>

                    <li><a href="{{ route('home') }}#why-section">Services</a></li>

                    <li><a href="{{ route('home') }}#contact-section">Contact</a></li>

                </ul>

            </div>

            <!-- COLONNE 3 -->
            <div class="footer-col">

                <h4>Contact</h4>

                <ul>

                    <li>
                        <i class="fa-solid fa-location-dot"></i>
                        Cocody, Abidjan
                    </li>

                    <li>
                        <i class="fa-solid fa-phone"></i>
                        +225 01 41 60 12 78
                    </li>

                    <li>
                        <i class="fa-solid fa-envelope"></i>
                        contact@dsholding.ci
                    </li>

                </ul>

            </div>

            <!-- COLONNE 4 -->
            <div class="footer-col">

                <h4>Newsletter</h4>

                <p>
                    Recevez nos dernières offres.
                </p>

                <form class="newsletter-form">

                    <input
                        type="email"
                        placeholder="Votre email">

                    <button type="submit">

                        <i class="fa-solid fa-paper-plane"></i>

                    </button>

                </form>

                <div class="social-links">

                    <a href="#">
                        <i class="fab fa-facebook-f"></i>
                    </a>

                    <a href="#">
                        <i class="fab fa-instagram"></i>
                    </a>

                    <a href="#">
                        <i class="fab fa-whatsapp"></i>
                    </a>

                    <a href="#">
                        <i class="fab fa-linkedin-in"></i>
                    </a>

                </div>

            </div>

        </div>

        <div class="footer-bottom">

            <p>
                © {{ now()->year }}
                DS HOLDING • Tous droits réservés.
            </p>

        </div>

    </div>

</footer>

<button id="backToTop">

    <i class="fa-solid fa-arrow-up"></i>

</button>
