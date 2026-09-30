@php
    $status = old('status', $unite->exists && $unite->status !== App\Enums\UnitStatus::Active ? 'inactif' : 'actif');
@endphp

<div class="etablissement-page">

    <!-- En-tête -->

    <div class="page-card">

        <div class="page-card-header">

            <div>

                <h2>

                    {{ $unite->exists ? 'Modifier l\'unité' : 'Ajouter une unité' }}

                </h2>

                <p>

                    {{ $unite->exists ? 'Unité de l\'établissement' : 'Ajoutez une nouvelle unité à l\'établissement' }} <strong>{{ $etablissement->name }}</strong>.

                </p>

            </div>

            <a
                href="{{ route('admin.unites.index') }}"
                class="btn-secondary">

                <i class="fa-solid fa-arrow-left"></i>

                Retour

            </a>

        </div>

    </div>

    <!-- les alertes -->
    @include('partials.flash')

    <form
        id="uniteForm"
        class="formulaire-etablissement"
        method="POST"
        action="{{ $action }}"
        enctype="multipart/form-data">

        @csrf

        @if ($unite->exists)
            @method('PUT')
        @endif

        <div
            class="carte-formulaire etape-contenu active">

            <div class="carte-body">

                <div class="grille-formulaire">

                    <!-- Type unité -->

                    <div class="groupe-formulaire large">

                        <label>

                            Type d'unités

                            <span>*</span>

                        </label>

                        <div class="types-etablissements">

                            @foreach ($typesUnite as $type)

                                <label class="carte-type">

                                    <input
                                        type="radio"
                                        name="type_unite_id"
                                        value="{{ $type->id }}"
                                        @checked((int) old('type_unite_id', $unite->unit_type_id) === $type->id)>

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
                            id="error-type_unite">

                        </small>

                    </div>

                    <div class="form-row large">

                        <!-- Nom -->
                        <div class="groupe-formulaire">

                            <label>

                                Nom de l'unité

                                <span>*</span>

                            </label>

                            <div class="input-icon">

                                <input
                                    type="text"
                                    name="nom"
                                    id="nom"
                                    placeholder="Ex : Chambre double vue mer"
                                    value="{{ old('nom', $unite->name) }}">

                            </div>

                            <small
                                class="field-error"
                                id="error-nom">

                            </small>

                        </div>

                        <!-- Nombre d'unités identiques -->
                        <div class="groupe-formulaire">

                            <label>

                                Nombre d'unités identiques

                                <span>*</span>

                            </label>

                            <div class="input-icon">

                                <!-- <i class="fa-solid fa-hotel"></i> -->

                                <input
                                    type="number"
                                    name="nombre_unite"
                                    id="nombre_unite"
                                    placeholder="Ex : 5"
                                    min="1"
                                    value="{{ old('nombre_unite', $unite->quantity ?? 1) }}">

                            </div>

                            <small
                                class="field-error"
                                id="error-nombre_unite">

                            </small>

                        </div>

                        <!-- Nombre de chambre -->
                        <div class="groupe-formulaire">

                            <label>

                                Nombre de chambres

                                <span>*</span>

                            </label>

                            <div class="input-icon">

                                <i class="fa-solid fa-hotel"></i>

                                <input
                                    type="number"
                                    name="nombre_chambre"
                                    id="nombre_chambre"
                                    placeholder="Ex : 5"
                                    min="1"
                                    value="{{ old('nombre_chambre', $unite->bedrooms ?? 1) }}">

                            </div>

                            <small
                                class="field-error"
                                id="error-nombre_chambre">

                            </small>

                        </div>

                        <!-- Nombre de lits -->
                        <div class="groupe-formulaire">

                            <label>

                                Nombre de lits

                                <span>*</span>

                            </label>

                            <div class="input-icon">

                                <i class="fa-solid fa-bed"></i>

                                <input
                                    type="number"
                                    name="nombre_lit"
                                    id="nombre_lit"
                                    placeholder="Ex : 5"
                                    min="1"
                                    value="{{ old('nombre_lit', $unite->beds ?? 1) }}">

                            </div>

                            <small
                                class="field-error"
                                id="error-nombre_lit">

                            </small>

                        </div>

                        <!-- Nombre de salles de bain -->
                        <div class="groupe-formulaire">

                            <label>

                                Nombre de salles de bain

                                <span>*</span>

                            </label>

                            <div class="input-icon">

                                <i class="fa-solid fa-bath"></i>

                                <input
                                    type="number"
                                    name="nombre_salle_bain"
                                    id="nombre_salle_bain"
                                    placeholder="Ex : 5"
                                    min="1"
                                    value="{{ old('nombre_salle_bain', $unite->bathrooms ?? 1) }}">

                            </div>

                            <small
                                class="field-error"
                                id="error-nombre_salle_bain">

                            </small>

                        </div>

                        <!-- Superficie (m²) -->
                        <div class="groupe-formulaire">

                            <label>

                                Superficie (m²)

                                <!-- <span>*</span> -->

                            </label>

                            <div class="input-icon">

                                <!-- <i class="fa-solid fa-bath"></i> -->

                                <input
                                    type="number"
                                    name="surperficie"
                                    id="surperficie"
                                    placeholder="Ex : 100 m2"
                                    min="1"
                                    value="{{ old('surperficie', $unite->size_m2) }}">

                            </div>

                        </div>

                    </div>

                    <div class="form-row large">

                        <!-- Capacité -->

                        <div class="groupe-formulaire">

                            <label>

                                Capacité (adultes)

                                <span>*</span>

                            </label>

                            <div class="input-icon">

                                <i class="fa-solid fa-user-group"></i>

                                <input
                                    type="number"
                                    name="capacite"
                                    id="capacite"
                                    placeholder="Ex : 2"
                                    min="1"
                                    value="{{ old('capacite', $unite->max_adults ?? 2) }}">

                            </div>

                        </div>

                        <!-- Prix -->
                        <div class="groupe-formulaire">

                            <label>

                                Prix

                                <span>*</span>

                            </label>

                            <div class="input-icon">

                                <i class="fa-solid fa-money-bill"></i>

                                <input
                                    type="text"
                                    name="prix"
                                    id="prix"
                                    placeholder="Ex : 20 000 FCFA"
                                    value="{{ old('prix', $unite->base_price) }}">

                            </div>

                            <small
                                class="field-error"
                                id="error-prix">

                            </small>

                        </div>

                        <!-- Nombre d'unités identiques -->
                        <div class="groupe-formulaire">

                            <label>

                                Prix promotionnel

                                <!-- <span>*</span> -->

                            </label>

                            <div class="input-icon">

                                <i class="fa-solid fa-money-bill"></i>

                                <input
                                    type="number"
                                    name="prix_promo"
                                    id="prix_promo"
                                    placeholder="Ex : 20 000 FCFA"
                                    min="0"
                                    value="{{ old('prix_promo', $unite->promo_price) }}">

                            </div>

                        </div>

                    </div>

                    <!-- Description -->

                    <div class="groupe-formulaire large">

                        <label>

                            Description

                        </label>

                        <textarea
                            name="description"
                            placeholder="Décrivez votre unité, ses services, son environnement et ses principaux atouts...">{{ old('description', $unite->description) }}</textarea>

                        <small>

                            Cette description sera visible par les clients sur la plateforme.

                        </small>

                    </div>

                    <!-- équipements -->

                    <div class="groupe-formulaire large">

                        <label>

                            Équipements disponibles

                        </label>

                        <div class="equipements-section">

                            @foreach ($equipements as $equipement)

                                <label class="carte-type">

                                    <input
                                        type="checkbox"
                                        name="equipement_id[]"
                                        value="{{ $equipement->id }}"
                                        @checked(in_array($equipement->id, array_map('intval', old('equipement_id', $unite->equipments->modelKeys()))))>

                                    <div class="carte-type-body">

                                        <div class="type-icone">

                                            <i class="fa-solid {{ $equipement->fa_icon }}"></i>

                                        </div>

                                        <h4>

                                            {{ $equipement->name }}

                                        </h4>

                                        <p>

                                            {{ $equipement->category->label() }}

                                        </p>

                                    </div>

                                </label>

                            @endforeach

                        </div>

                        <small
                            class="field-error"
                            id="error-equipement">

                        </small>

                    </div>

                    <!-- GALERIE -->

                    <div class="groupe-formulaire large">

                        <div class="media-section">

                            <label>

                                Images

                                <span>*</span>

                            </label>

                            <input
                                type="hidden"
                                id="gallery_existing"
                                name="gallery_existing">

                            <input
                                type="hidden"
                                id="deleted_gallery"
                                name="deleted_gallery">

                            <input
                                type="hidden"
                                id="gallery_cover"
                                name="gallery_cover">

                            <input
                                type="hidden"
                                id="principal_image"
                                name="principal_image">

                            <input
                                type="file"
                                id="galleryInput"
                                name="images[]"
                                accept=".jpg,.jpeg,.png,.webp"
                                multiple
                                hidden>

                            <div
                                id="galleryDropzone"
                                class="dropzone">

                                <div class="dropzone-content">

                                    <i class="fa-solid fa-cloud-arrow-up"></i>

                                    <h5>

                                        Déposez vos images ici

                                    </h5>

                                    <p>

                                        ou cliquez pour sélectionner des fichiers

                                    </p>

                                </div>

                            </div>

                            <div class="gallery-toolbar">

                                <strong id="galleryCount">

                                    0 / 20 images

                                </strong>

                                <span>

                                    Maximum 5 Mo par image

                                </span>

                            </div>

                            <div class="upload-progress">

                                <div id="uploadProgressBar"></div>

                            </div>

                            <small
                                id="error-gallery"
                                class="field-error"></small>

                            <div
                                id="galleryPreview"
                                class="gallery-preview"></div>

                        </div>

                    </div>

                    <div class="publication-section large">

                        <h4>

                            <i class="fa-solid fa-eye"></i>

                            Statut de l'unité

                        </h4>

                        <div class="publication-grid">

                            <!-- ACTIF -->

                            <label class="publication-card">

                                <input
                                    type="radio"
                                    name="status"
                                    value="actif"
                                    @checked($status === 'actif')>

                                <div class="publication-content">

                                    <div class="publication-icon actif">

                                        <i class="fa-solid fa-circle-check"></i>

                                    </div>

                                    <div>

                                        <h5>

                                            Actif

                                        </h5>

                                        <p>

                                            L'unité sera immédiatement visible et pourra recevoir des réservations.

                                        </p>

                                    </div>

                                </div>

                            </label>

                            <!-- INACTIF -->

                            <label class="publication-card">

                                <input
                                    type="radio"
                                    name="status"
                                    value="inactif"
                                    @checked($status === 'inactif')>

                                <div class="publication-content">

                                    <div class="publication-icon inactif">

                                        <i class="fa-solid fa-eye-slash"></i>

                                    </div>

                                    <div>

                                        <h5>

                                            Inactif

                                        </h5>

                                        <p>

                                            L'unité sera enregistrée mais restera invisible jusqu'à son activation.

                                        </p>

                                    </div>

                                </div>

                            </label>

                        </div>

                    </div>

                </div>

            </div>

        </div>

        <div class="form-actions">

            <button
                type="submit"
                class="btn-save">

                <i class="fa-solid {{ $unite->exists ? 'fa-pen-to-square' : 'fa-floppy-disk' }}"></i>

                {{ $unite->exists ? 'Mettre à jour' : 'Enregistrer' }}

            </button>

        </div>

    </form>

</div>

@php
    $unitGallery = $unite->images->map(fn ($image) => ['id' => $image->id, 'url' => $image->url])->values();
@endphp

<script>
    window.unitGallery = @json($unitGallery);
</script>

@push('styles')
    @vite('resources/css/admin/etablissement.css')
@endpush

@push('scripts')
    @vite('resources/js/admin/unite.js')
@endpush
