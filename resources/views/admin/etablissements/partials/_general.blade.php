<div
    class="carte-formulaire etape-contenu active"
    data-step="1">

    <div class="carte-header">

        <div class="carte-titre">

            <div class="carte-icone">

                <i class="fa-solid fa-circle-info"></i>

            </div>

            <div>

                <h3>

                    Informations générales

                </h3>

                <p>

                    Configurez les informations principales de votre établissement.

                </p>

            </div>

        </div>

        <span class="badge-etape">

            Étape 1 / 6

        </span>

    </div>

    <div class="carte-body">

        <div class="grille-formulaire">

            <!-- Type -->

            <div class="groupe-formulaire large">

                <label>

                    Type d'établissement

                    <span>*</span>

                </label>

                <div class="types-etablissements">

                    @foreach ($typesEtablissement as $type)

                        <label class="carte-type">

                            <input
                                type="radio"
                                name="type_etablissement_id"
                                value="{{ $type->id }}"
                                @checked((int) old('type_etablissement_id', $etablissement->property_type_id) === $type->id)>

                            <div class="carte-type-body">

                                <div class="type-icone">

                                    <i class="fa-solid {{ $type->fa_icon }}"></i>

                                </div>

                                <h4>

                                    {{ $type->name }}

                                </h4>

                                <p>

                                    {{ $type->description }}

                                </p>

                            </div>

                        </label>

                    @endforeach

                </div>

                <small
                    class="field-error"
                    id="error-type">

                </small>

            </div>

            <!-- Nom -->

            <div class="groupe-formulaire">

                <label>

                    Nom de l'établissement

                    <span>*</span>

                </label>

                <div class="input-icon">

                    <i class="fa-solid fa-hotel"></i>

                    <input
                        type="text"
                        name="nom"
                        id="nom"
                        placeholder="Ex : Hôtel Palm Club"
                        autocomplete="off"
                        value="{{ old('nom', $etablissement->name) }}">

                </div>

                <small
                    class="field-error"
                    id="error-nom">

                </small>

            </div>

            <!-- Description -->

            <div class="groupe-formulaire large">

                <label>

                    Description

                </label>

                <textarea
                    name="description"
                    placeholder="Décrivez votre établissement, ses services, son environnement et ses principaux atouts...">{{ old('description', $etablissement->description) }}</textarea>

                <small>

                    Cette description sera visible par les clients sur la plateforme.

                </small>

            </div>

        </div>

    </div>

</div>
