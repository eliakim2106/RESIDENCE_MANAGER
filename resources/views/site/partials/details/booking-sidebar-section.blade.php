<section class="booking-card" id="reservation" tabindex="-1" aria-labelledby="booking-title">

    <div class="booking-card-header">
        <h3 id="booking-title">
            Réserver votre séjour
        </h3>

        <span class="booking-card-rating">
            <i class="fa-solid fa-star"></i>
            4.8
        </span>
    </div>

    <div class="booking-form">

        <div class="booking-dates">

            <div class="form-group">

                <label for="booking-check-in">Date d'arrivée</label>

                <input type="date" id="booking-check-in" name="arrivee" min="{{ now()->toDateString() }}" data-placeholder="Choisir" data-datepicker data-datepicker-start="reservation">

            </div>

            <div class="form-group">

                <label for="booking-check-out">Date de départ</label>

                <input type="date" id="booking-check-out" name="depart" min="{{ now()->addDay()->toDateString() }}" data-placeholder="Choisir" data-datepicker data-datepicker-end="reservation">

            </div>

        </div>

        <div class="form-group">

            <label for="booking-guests">Voyageurs</label>

            <select id="booking-guests">

                <option>1 voyageur</option>
                <option>2 voyageurs</option>
                <option>3 voyageurs</option>
                <option>4 voyageurs</option>

            </select>

        </div>

        <div class="price-box">

            <strong>35 000 FCFA</strong>

            <span>/ nuit</span>

        </div>

        <button type="button" class="btn-booking">

            Vérifier la disponibilité

        </button>

    </div>

    <ul class="booking-benefits">

        <li>Meilleur prix garanti</li>

        <li>Confirmation instantanée</li>

        <li>Paiement sécurisé</li>

    </ul>

</section>
