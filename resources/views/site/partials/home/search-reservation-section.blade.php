<section class="search-section">

    <div class="container">

        <div class="search-card">

            <div class="search-item">

                <div>

                    <label>Destination</label>

                    <input
                        type="text"
                        placeholder="Ville, quartier, résidence">

                </div>

            </div>

            <div class="divider"></div>

            <div class="search-item">

                <div>

                    <label>Arrivée</label>

                    <input type="date" id="checkin" min="{{ now()->toDateString() }}">

                </div>

            </div>

            <div class="divider"></div>

            <div class="search-item">

                <div>

                    <label>Départ</label>

                    <input type="date" id="checkout" min="{{ now()->toDateString() }}">

                </div>

            </div>

            <div class="divider"></div>

            <div class="search-item">

                <div>

                    <label>Voyageurs</label>

                    <select>

                        <option>1 personne</option>
                        <option>2 personnes</option>
                        <option>3 personnes</option>
                        <option>4 personnes</option>
                        <option>5+</option>

                    </select>

                </div>

            </div>

            <button class="search-btn">

                Rechercher

            </button>

        </div>

    </div>

</section>
