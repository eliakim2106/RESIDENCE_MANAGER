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

                    Accueil & conditions

                </h3>

                <p>

                    Contact, horaires et conditions de séjour : elles s’appliquent à toutes les réservations.

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
                            value="{{ old('telephone', $etablissement->phone ? App\Http\Requests\Admin\PropertyRequest::nationalPhone($etablissement->phone) : '') }}">

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

                    <label for="arrivee_jusqua">

                        Arrivée jusqu’à

                        <em class="label-optional">(facultatif)</em>

                    </label>

                    <div class="time-input">

                        <i class="fa-regular fa-moon"></i>

                        <input
                            id="arrivee_jusqua"
                            type="time"
                            name="arrivee_jusqua"
                            value="{{ old('arrivee_jusqua', $etablissement->check_in_until ? substr($etablissement->check_in_until, 0, 5) : '') }}">

                    </div>

                    <small
                        id="error-arrivee_jusqua"
                        class="field-error">
                    </small>

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

                    <small>Le jour du départ.</small>

                </div>

            </div>

        </div>

        <!-- CONDITIONS DE SÉJOUR -->

        @php
            $policy = old('politique_annulation', ($etablissement->cancellation_policy ?? App\Enums\CancellationPolicy::Flexible)->value);
            $policyDetails = [
                'flexible' => ['Flexible', 'Annulation gratuite jusqu’à 24 h avant l’arrivée.', 'fa-feather', 'good'],
                'moderate' => ['Modérée', 'Annulation gratuite jusqu’à 5 jours avant l’arrivée.', 'fa-scale-balanced', 'warning'],
                'strict' => ['Stricte', 'Non remboursable une fois la réservation confirmée.', 'fa-lock', 'critical'],
            ];
        @endphp

        <div class="section-formulaire">

            <h4>

                <i class="fa-solid fa-file-contract"></i>

                Politique d’annulation

                <span class="required-star">*</span>

            </h4>

            <p class="section-intro">
                Elle est rappelée au client au moment de réserver et fixe la date limite d’annulation gratuite (et donc les remboursements).
            </p>

            <div class="policy-grid" role="radiogroup" aria-label="Politique d’annulation">

                @foreach ($policyDetails as $value => [$label, $text, $icon, $tone])

                    <label class="policy-card">

                        <input type="radio" name="politique_annulation" value="{{ $value }}" @checked($policy === $value)>

                        <span class="policy-card-body">

                            <span class="policy-card-icon tone-{{ $tone }}"><i class="fa-solid {{ $icon }}"></i></span>

                            <strong>{{ $label }}</strong>

                            <small>{{ $text }}</small>

                            <span class="policy-card-check"><i class="fa-solid fa-check"></i></span>

                        </span>

                    </label>

                @endforeach

            </div>

            <small id="error-politique_annulation" class="field-error"></small>

        </div>

        <div class="section-formulaire">

            <h4>

                <i class="fa-solid fa-list-check"></i>

                Règles de la maison

            </h4>

            <div class="house-toggles">

                @foreach (['animaux' => ['allows_pets', 'Animaux acceptés', 'fa-paw'], 'fumeurs' => ['allows_smoking', 'Fumeurs autorisés', 'fa-smoking'], 'fetes' => ['allows_parties', 'Fêtes et événements', 'fa-champagne-glasses']] as $name => [$column, $label, $icon])

                    <input type="hidden" name="{{ $name }}" value="0">

                    <label class="house-toggle">

                        <input type="checkbox" name="{{ $name }}" value="1" @checked(old($name, $etablissement->{$column}))>

                        <span class="house-toggle-body">

                            <i class="fa-solid {{ $icon }}"></i>

                            <span>{{ $label }}</span>

                            <span class="house-toggle-state" aria-hidden="true"></span>

                        </span>

                    </label>

                @endforeach

            </div>

            <div class="groupe-formulaire large house-rules">

                <label for="reglement">

                    Règlement intérieur

                    <em class="label-optional">(facultatif)</em>

                </label>

                <textarea
                    id="reglement"
                    name="reglement"
                    rows="4"
                    maxlength="3000"
                    placeholder="Ex : Pièce d’identité exigée à l’arrivée. Pas de bruit après 22 h. Visiteurs non autorisés après 20 h.">{{ old('reglement', $etablissement->house_rules) }}</textarea>

                <small>Affiché sur la fiche de l’établissement et rappelé sur le bon de réservation.</small>

                <small id="error-reglement" class="field-error"></small>

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
