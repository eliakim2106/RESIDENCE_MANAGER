<div
    class="carte-formulaire etape-contenu"
    data-step="4">

    <div class="carte-header">

        <div class="carte-titre">

            <div class="carte-icone">

                <i class="fa-solid fa-images"></i>

            </div>

            <div>

                <h3>

                    Médias

                </h3>

                <p>

                    Ajoutez le logo et les photos de votre établissement.

                </p>

            </div>

        </div>

        <span class="badge-etape">

            Étape 4 / 6

        </span>

    </div>

    <div class="carte-body">

        <!-- ====================================================== -->
        <!-- LOGO -->
        <!-- ====================================================== -->

        <div class="media-section">

            <h4>
                <i class="fa-solid fa-image"></i>
                Logo
            </h4>

            <input
                type="file"
                id="logoInput"
                name="logo"
                accept=".jpg,.jpeg,.png,.webp"
                hidden>

            <input
                type="hidden"
                id="deleted_logo"
                name="deleted_logo"
                value="0">

            <div
                id="logoDropzone"
                class="logo-card">

                <div id="logoPreview"></div>

            </div>

            <div class="media-actions">

                <button
                    type="button"
                    id="btnChangeLogo"
                    class="btn-primary">

                    <i class="fa-solid fa-upload"></i>

                    Choisir un logo

                </button>

            </div>

            <div class="media-info">

                <span>

                    <i class="fa-solid fa-circle-info"></i>

                    PNG • JPG • WEBP

                </span>

                <span>

                    Maximum 2 Mo

                </span>

            </div>

            <small
                id="error-logo"
                class="field-error"></small>

        </div>

        <!-- ====================================================== -->
        <!-- GALERIE -->
        <!-- ====================================================== -->

        <div class="media-section">

            <h4>

                <i class="fa-solid fa-images"></i>

                Galerie

                <span class="required">*</span>

            </h4>

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
                name="gallery[]"
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

</div>

@php
    $mediaConfig = [
        'edit' => $etablissement->exists,
        'logo' => $etablissement->logo_path,
        'logoUrl' => $etablissement->logo_url,
        'gallery' => $etablissement->images->map(fn ($image) => [
            'id' => $image->id,
            'url' => $image->url,
            'cover' => $image->is_cover,
        ])->values(),
    ];
@endphp

<script>
    window.mediaConfig = @json($mediaConfig);
</script>
