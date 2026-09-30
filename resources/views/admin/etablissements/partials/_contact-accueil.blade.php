<div
    class="carte-formulaire etape-contenu"
    data-step="3">

    <div class="carte-header">

        <div class="carte-titre">

            <div class="carte-icone">

                <i class="fa-solid fa-address-book"></i>

            </div>

            <div>

                <h3>

                    Contact & Accueil

                </h3>

                <p>

                    Configurez les informations de contact et les horaires de votre établissement.

                </p>

            </div>

        </div>

        <span class="badge-etape">

            Étape 3 / 6

        </span>

    </div>

    <div class="carte-body">

        <!-- CONTACT -->

        <div class="section-formulaire">

            <h4>

                <i class="fa-solid fa-phone"></i>

                Contact

            </h4>

            <div class="grille-formulaire">

                <!-- Téléphone -->

                <div class="groupe-formulaire">

                    <label>

                        Téléphone

                        <span>*</span>

                    </label>

                    <div class="input-icon">

                        <span class="prefix">+225</span>

                        <input
                            id="telephone"
                            type="text"
                            name="telephone"
                            maxlength="10"
                            placeholder="0701020304"
                            value="{{ old('telephone', $etablissement->phone) }}">

                    </div>

                    <small
                        id="error-telephone"
                        class="field-error">
                    </small>

                </div>

                <!-- Email -->

                <div class="groupe-formulaire">

                    <label>

                        Email

                    </label>

                    <div class="input-icon">

                        <i class="fa-solid fa-envelope"></i>

                        <input
                            id="email"
                            type="email"
                            name="email"
                            placeholder="contact@hotel.com"
                            autocomplete="off"
                            value="{{ old('email', $etablissement->email) }}">

                    </div>

                    <small
                        id="error-email"
                        class="field-error">
                    </small>

                </div>

                <!-- Site -->

                <div class="groupe-formulaire large">

                    <label>

                        Site web

                    </label>

                    <div class="input-icon">

                        <i class="fa-solid fa-globe"></i>

                        <input
                            id="site_web"
                            type="url"
                            name="site_web"
                            placeholder="https://"
                            value="{{ old('site_web', $etablissement->website) }}">

                    </div>

                </div>

            </div>

        </div>

        <!-- ACCUEIL -->

        <div class="section-formulaire">

            <h4>

                <i class="fa-solid fa-door-open"></i>

                Accueil

            </h4>

            <div class="grille-formulaire">

                <div class="groupe-formulaire">

                    <label>

                        Heure d'arrivée

                    </label>

                    <div class="time-input">

                        <i class="fa-regular fa-clock"></i>

                        <input
                            id="check_in"
                            type="time"
                            name="check_in"
                            value="{{ old('check_in', $etablissement->check_in_from ? substr($etablissement->check_in_from, 0, 5) : '') }}">

                    </div>

                </div>

                <div class="groupe-formulaire">

                    <label>

                        Heure de départ

                    </label>

                    <div class="time-input">

                        <i class="fa-solid fa-door-open"></i>

                        <input
                            id="check_out"
                            type="time"
                            name="check_out"
                            value="{{ old('check_out', $etablissement->check_out_until ? substr($etablissement->check_out_until, 0, 5) : '') }}">

                    </div>

                    <small
                        id="error-checkout"
                        class="field-error">

                    </small>

                </div>

            </div>

        </div>

        <!-- ETOILES -->

        <div class="section-formulaire">

            <h4>

                <i class="fa-solid fa-star"></i>

                Classement

            </h4>

            <div
                class="rating-stars"
                id="ratingStars">

                @for ($i = 1; $i <= 5; $i++)

                    <i
                        class="fa-regular fa-star star"
                        data-value="{{ $i }}"></i>

                @endfor

            </div>

            <input
                type="hidden"
                id="etoile"
                name="etoile"
                value="{{ old('etoile', $etablissement->star_rating ?? 0) }}">

        </div>

        <!-- SWITCH -->

        <div class="section-formulaire">

            <h4>

                <i class="fa-solid fa-building"></i>

                Gestion des unités

            </h4>

            <label class="switch">

                <input
                    type="checkbox"
                    id="gestion_unites"
                    name="gestion_unites"
                    value="1"
                    @checked(old('gestion_unites', $etablissement->exists ? $etablissement->manages_units : true))>

                <span class="slider"></span>

            </label>

            <p class="switch-description">

                Activez cette option si votre établissement possède plusieurs unités (chambres, appartements, villas...).

            </p>

        </div>

    </div>

</div>
