<section class="similar-residences-section">

    <h3 class="section-title">
        Résidences similaires
    </h3>

    <div class="similar-residences-grid">

        <article class="similar-card">

            <div class="similar-image">

                <img
                    src="{{ asset('assets/images/residences/residence-1.png') }}"
                    alt="Résidence">

                <span class="premium-badge">
                    <i class="fa-solid fa-crown"></i>
                    Premium
                </span>

                <button class="favorite-btn">
                    <i class="fa-regular fa-heart"></i>
                </button>

            </div>

            <div class="similar-content">

                <h4>
                    DS Riviera Prestige
                </h4>

                <p class="location">
                    Cocody Riviera
                </p>

                <div class="card-footer">

                    <div class="price">
                        45 000 FCFA
                        <span>/ nuit</span>
                    </div>

                    <div class="rating">
                        ⭐ 4.9
                        <small>(98 avis)</small>
                    </div>

                </div>

                <a
                    href="{{ route('residences.show') }}"
                    class="details-btn">

                    Voir détails

                </a>

            </div>

        </article>

        <!-- Dupliquer 3 autres cartes -->

    </div>

</section>
