<div
    class="carte-formulaire etape-contenu"
    data-step="6">

    <!--==========================================
        HEADER
    ===========================================-->

    <div class="carte-header">

        <div class="carte-titre">

            <div class="carte-icone">

                <i class="fa-solid fa-magnifying-glass-chart"></i>

            </div>

            <div>

                <h3>

                    Référencement (SEO)

                </h3>

                <p>

                    Optimisez la visibilité de votre établissement sur les moteurs de recherche.

                </p>

            </div>

        </div>

        <span class="badge-etape">

            Étape 6 / 6

        </span>

    </div>

    <!--==========================================
        BODY
    ===========================================-->

    <div class="carte-body">

        <!--=========================
            TITRE SEO
        ==========================-->

        <div class="groupe-formulaire">

            <label>

                Titre SEO

            </label>

            <div class="input-icon">
                <input
                    type="text"
                    id="meta_title"
                    name="meta_title"
                    maxlength="60"
                    placeholder="Ex : Hôtel Palm Club Abidjan"
                    value="{{ old('meta_title', $etablissement->meta_title ?: Str::limit((string) $etablissement->name, 60, '')) }}">
            </div>

            <div class="seo-counter">

                <span id="titleCounter">

                    0

                </span>

                /60 caractères

            </div>

            <small
                id="error-meta-title"
                class="field-error">

            </small>

        </div>

        <!--=========================
            DESCRIPTION
        ==========================-->

        <div class="groupe-formulaire">

            <label>

                Meta description

            </label>

            <textarea
                id="meta_description"
                name="meta_description"
                rows="5"
                maxlength="160"
                placeholder="Décrivez votre établissement...">{{ old('meta_description', $etablissement->meta_description) }}</textarea>

            <div class="seo-counter">

                <span id="descriptionCounter">

                    0

                </span>

                /160 caractères

            </div>

            <small
                id="error-meta-description"
                class="field-error">

            </small>

        </div>

        <!--=========================
            SLUG
        ==========================-->

        <div class="groupe-formulaire">

            <label>

                URL de l'établissement

            </label>

            <div class="slug-input">

                <span>

                    {{ url('etablissement') }}/

                </span>

                <input
                    type="text"
                    id="slug"
                    name="slug"
                    data-base-url="{{ url('etablissement') }}/"
                    placeholder="hotel-palm-club-abidjan"
                    value="{{ old('slug', $etablissement->slug) }}">

            </div>

            <small
                id="error-slug"
                class="field-error">

            </small>

        </div>

        <!--=========================
            APERCU GOOGLE
        ==========================-->

        <div class="seo-preview">

            <h4>

                <i class="fa-brands fa-google"></i>

                Aperçu Google

            </h4>

            <div class="google-preview">

                <div
                    id="googleTitle"
                    class="google-title">

                    Titre SEO

                </div>

                <div
                    id="googleUrl"
                    class="google-url">

                    {{ url('etablissement') }}/

                </div>

                <div
                    id="googleDescription"
                    class="google-description">

                    Votre description apparaîtra ici...

                </div>

            </div>

        </div>

    </div>

</div>
