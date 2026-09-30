<section class="home-cta">
    <div class="container">
        <div class="home-cta-card" data-reveal>
            <img src="{{ asset('assets/images/home/slide-2.webp') }}" alt="" class="home-cta-bg" loading="lazy">

            <div class="home-cta-content">
                <span class="home-kicker home-kicker-light">Réservation en ligne</span>
                <h2>Prêt à réserver votre prochain séjour ?</h2>
                <p>Choisissez votre résidence, vos dates, et recevez la confirmation de votre séjour en quelques minutes.</p>
            </div>

            <div class="home-cta-actions">
                <a href="{{ route('residences.index') }}" class="site-btn site-btn-gold home-btn-lg">
                    Réserver maintenant
                    <i class="fa-solid fa-arrow-right"></i>
                </a>
                <a href="#contact" class="site-btn site-btn-ghost home-btn-lg">
                    Nous contacter
                </a>
            </div>
        </div>
    </div>
</section>
