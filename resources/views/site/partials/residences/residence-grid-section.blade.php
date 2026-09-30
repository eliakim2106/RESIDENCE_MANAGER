<section class="residence-list-section">

    <div class="container">

        <div class="residence-topbar">

            <h2>
                9 résidences trouvées
            </h2>

            <div class="sort-box">

                <label>Trier par</label>

                <select>
                    <option>Popularité</option>
                    <option>Prix croissant</option>
                    <option>Prix décroissant</option>
                    <option>Note</option>
                </select>

            </div>

        </div>

        <div class="residence-card-parent">
            <div class="residence-card">

                <div class="residence-image">

                    <span class="badge-popular">

                        Populaire

                    </span>

                    <button class="favorite-btn">

                        <i class="fa-regular fa-heart"></i>

                    </button>

                    <img
                        src="{{ asset('assets/images/residences/residence-1.png') }}"
                        alt="DS Palace">

                </div>

                <div class="residence-content">

                    <div class="rating">

                        ⭐ 4.8

                        <span>(124 avis)</span>

                    </div>

                    <span class="location">

                        <i class="fa-solid fa-location-dot"></i>

                        Cocody, Abidjan

                    </span>

                    <h3>

                        DS Palace

                    </h3>

                    <p>

                        Résidence meublée haut standing
                        avec vue panoramique.

                    </p>

                    <div class="features">

                        <span><i class="fa-solid fa-wifi"></i> WiFi</span>

                        <span><i class="fa-solid fa-water-ladder"></i> Piscine</span>

                        <span><i class="fa-solid fa-car"></i> Parking</span>

                        <span><i class="fa-solid fa-snowflake"></i> Climatisation</span>

                    </div>

                    <div class="price-row">

                        <div class="price">

                            <strong>35 000 FCFA / nuit </strong>

                        </div>

                        <a href="{{ route('residences.show') }}" class="btn-details">

                            Voir les détails

                            <i class="fa-solid fa-arrow-right"></i>

                        </a>

                    </div>

                </div>

            </div>
        </div>

        <div class="pagination">

            <a href="#" class="active">1</a>

            <a href="#">2</a>

            <a href="#">3</a>

            <a href="#">
                <i class="fa-solid fa-arrow-right"></i>
            </a>

        </div>

    </div>

</section>
