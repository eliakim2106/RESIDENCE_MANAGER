<section class="gallery-section">

    <div class="gallery-container">

        <div class="gallery-grid">

            <!-- IMAGE PRINCIPALE -->

            <div class="gallery-main">

                <img
                    id="mainResidenceImage"
                    src="{{ asset('assets/images/residences/details/residence-details-1.png') }}"
                    alt="DS Palace Premium">

                <!-- Fil d'Ariane -->

                <div class="gallery-breadcrumb">

                    <a href="{{ route('home') }}">Accueil</a>

                    <span>›</span>

                    <a href="{{ route('residences.index') }}">
                        Résidences
                    </a>

                    <span>›</span>

                    <strong>DS Palace Premium</strong>

                </div>

                <!-- Badge Premium -->

                <div class="gallery-badge">

                    <i class="fa-solid fa-crown"></i>

                    Premium

                </div>

                <!-- Navigation -->

                <button class="gallery-nav prev">

                    <i class="fa-solid fa-chevron-left"></i>

                </button>

                <button class="gallery-nav next">

                    <i class="fa-solid fa-chevron-right"></i>

                </button>

                <!-- Compteur -->

                <div class="gallery-counter">

                    1 / 18

                </div>

            </div>

            <!-- IMAGES SECONDAIRES -->

            <div class="gallery-side">

                <div class="gallery-thumb">

                    <img
                        src="{{ asset('assets/images/residences/details/residence-details-2.png') }}"
                        alt="Chambre Premium">

                </div>

                <div class="gallery-thumb">

                    <img
                        src="{{ asset('assets/images/residences/details/residence-details-3.png') }}"
                        alt="Piscine">

                </div>

                <div class="gallery-thumb">

                    <img
                        src="{{ asset('assets/images/residences/details/residence-details-4.png') }}"
                        alt="Cuisine">

                </div>

                <div class="gallery-last">

                    <img
                        src="{{ asset('assets/images/residences/details/residence-details-5.png') }}"
                        alt="Salle de bain">

                    <div class="gallery-overlay">

                        <strong>
                            Voir toutes
                        </strong>

                        <span>
                            18 photos
                        </span>

                    </div>

                </div>

            </div>

        </div>

    </div>

</section>
