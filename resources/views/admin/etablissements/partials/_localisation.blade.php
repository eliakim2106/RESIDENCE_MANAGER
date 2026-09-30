<div
    class="carte-formulaire etape-contenu"
    data-step="2">

    <div class="carte-header">

        <div class="carte-titre">

            <div class="carte-icone">

                <i class="fa-solid fa-location-dot"></i>

            </div>

            <div>

                <h3>

                    Localisation

                </h3>

                <p>

                    Indiquez où se situe votre établissement.

                </p>

            </div>

        </div>

        <span class="badge-etape">

            Étape 2 / 6

        </span>

    </div>

    <div class="carte-body">

        <div class="grille-formulaire">

            <!-- Ville -->

            <div class="groupe-formulaire">

                <label>

                    Ville

                    <span>*</span>

                </label>

                <div class="input-icon">

                    <i class="fa-solid fa-city"></i>

                    <select id="ville" name="city_id">

                        <option value="">Sélectionner une ville</option>

                        @foreach ($villes as $ville)
                            <option value="{{ $ville->id }}" @selected((int) old('city_id', $etablissement->city_id) === $ville->id)>
                                {{ $ville->name }}
                            </option>
                        @endforeach

                    </select>

                </div>

                <small
                    class="field-error"
                    id="error-ville">
                </small>

            </div>

            <!-- Commune -->

            <div class="groupe-formulaire">

                <label>

                    Commune

                    <span>*</span>

                </label>

                <div class="input-icon">

                    <i class="fa-solid fa-map-location-dot"></i>

                    <input
                        id="commune"
                        type="text"
                        name="commune"
                        placeholder="Ex : Cocody"
                        value="{{ old('commune', $etablissement->district) }}">

                </div>

                <small
                    class="field-error"
                    id="error-commune">
                </small>

            </div>

            <!-- Quartier -->

            <div class="groupe-formulaire">

                <label>

                    Quartier

                </label>

                <div class="input-icon">

                    <i class="fa-solid fa-road"></i>

                    <input
                        type="text"
                        name="quartier"
                        id="quartier"
                        placeholder="Ex : Angré"
                        value="{{ old('quartier', $etablissement->neighborhood) }}">

                </div>

            </div>

            <!-- Adresse -->

            <div class="groupe-formulaire large">

                <label>

                    Adresse complète

                    <span>*</span>

                </label>

                <textarea
                    id="adresse"
                    name="adresse"
                    rows="5"
                    placeholder="Ex : Rue des Jardins, Angré 8ème tranche">{{ old('adresse', $etablissement->address) }}</textarea>

                <small
                    class="field-error"
                    id="error-adresse">
                </small>

            </div>

        </div>

        <div class="gps-card">

            <div class="gps-header">

                <h4>

                    Coordonnées GPS

                </h4>

                <div class="map-actions">

                    <button
                        type="button"
                        id="btnGps"
                        class="btn-primary">

                        <i class="fa-solid fa-location-crosshairs"></i>

                        Utiliser ma position

                    </button>

                </div>

            </div>

            <div class="grille-formulaire">

                <div class="groupe-formulaire">

                    <label>

                        Latitude

                    </label>

                    <div class="input-icon">

                        <i class="fa-solid fa-location-crosshairs"></i>

                        <input
                            id="latitude"
                            type="text"
                            name="latitude"
                            value="{{ old('latitude', $etablissement->latitude) }}">

                    </div>

                </div>

                <div class="groupe-formulaire">

                    <label>

                        Longitude

                    </label>

                    <div class="input-icon">

                        <i class="fa-solid fa-location-crosshairs"></i>

                        <input
                            id="longitude"
                            type="text"
                            name="longitude"
                            value="{{ old('longitude', $etablissement->longitude) }}">

                    </div>

                </div>

                <p
                    id="gpsMessage"
                    class="gps-message">

                </p>
            </div>

        </div>

    </div>

</div>
