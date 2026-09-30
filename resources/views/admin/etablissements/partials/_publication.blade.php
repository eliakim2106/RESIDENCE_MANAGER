@php
    use App\Enums\PropertyStatus;

    $current = $etablissement->statut ?? PropertyStatus::Draft;
    $online = in_array($current, [PropertyStatus::Published, PropertyStatus::Pending, PropertyStatus::Suspended], true);
    $status = old('statut', $etablissement->exists ? ($online ? 'actif' : 'inactif') : 'actif');

    // Un administrateur publie directement ; un propriétaire soumet à validation
    if (auth()->user()->isAdmin()) {
        $options = [
            'actif' => ['Actif', "L'établissement sera immédiatement visible et pourra recevoir des réservations.", "L'établissement sera publié immédiatement après son enregistrement."],
            'inactif' => ['Inactif', "L'établissement sera enregistré mais restera invisible jusqu'à son activation.", "L'établissement sera enregistré mais restera masqué jusqu'à son activation."],
        ];
        $intro = 'Choisissez si cet établissement est immédiatement visible sur la plateforme.';
    } else {
        $options = [
            'actif' => match ($current) {
                PropertyStatus::Published => ['En ligne', "L'établissement reste visible et continue de recevoir des réservations.", "L'établissement reste en ligne ; vos modifications sont visibles dès l'enregistrement."],
                PropertyStatus::Pending => ['En attente de validation', 'Votre demande est en cours d’examen par un administrateur.', 'Votre demande de publication reste en cours d’examen.'],
                PropertyStatus::Suspended => ['Suspendu', 'Seul un administrateur peut rétablir la publication.', 'L’établissement reste suspendu : contactez l’administration pour le rétablir.'],
                default => ['Soumettre pour validation', 'Un administrateur vérifiera votre établissement avant sa mise en ligne.', "L'établissement sera envoyé pour validation et publié dès son approbation."],
            },
            'inactif' => ['Brouillon', "L'établissement est enregistré sans être visible ni envoyé pour validation.", "L'établissement sera enregistré comme brouillon, invisible sur le site."],
        ];
        $intro = 'Tout nouvel établissement est vérifié par un administrateur avant d’être publié.';
    }
@endphp

<div
    class="carte-formulaire etape-contenu"
    data-step="5">

    <!-- ==========================================
        HEADER
    =========================================== -->

    <div class="carte-header">

        <div class="carte-titre">

            <div class="carte-icone">

                <i class="fa-solid fa-globe"></i>

            </div>

            <div>

                <h3>

                    Publication

                </h3>

                <p>

                    {{ $intro }}

                </p>

            </div>

        </div>

        <span class="badge-etape">

            Étape 5 / 6

        </span>

    </div>

    <!-- ==========================================
        CONTENU
    =========================================== -->

    <div class="carte-body">

        <div class="publication-section">

            <h4>

                <i class="fa-solid fa-eye"></i>

                Statut de l'établissement

            </h4>

            <div class="publication-grid">

                <!-- ACTIF -->

                <label class="publication-card">

                    <input
                        type="radio"
                        name="statut"
                        value="actif"
                        data-resume="{{ $options['actif'][2] }}"
                        @checked($status === 'actif')>

                    <div class="publication-content">

                        <div class="publication-icon actif">

                            <i class="fa-solid fa-circle-check"></i>

                        </div>

                        <div>

                            <h5>

                                {{ $options['actif'][0] }}

                            </h5>

                            <p>

                                {{ $options['actif'][1] }}

                            </p>

                        </div>

                    </div>

                </label>

                <!-- INACTIF -->

                <label class="publication-card">

                    <input
                        type="radio"
                        name="statut"
                        value="inactif"
                        data-resume="{{ $options['inactif'][2] }}"
                        @checked($status === 'inactif')>

                    <div class="publication-content">

                        <div class="publication-icon inactif">

                            <i class="fa-solid fa-eye-slash"></i>

                        </div>

                        <div>

                            <h5>

                                {{ $options['inactif'][0] }}

                            </h5>

                            <p>

                                {{ $options['inactif'][1] }}

                            </p>

                        </div>

                    </div>

                </label>

            </div>

        </div>

        <!-- ==========================================
            RESUME
        =========================================== -->

        <div class="publication-section">

            <h4>

                <i class="fa-solid fa-clipboard-check"></i>

                Résumé

            </h4>

            <div class="publication-resume">

                <div class="resume-item">

                    <i class="fa-solid fa-circle-info"></i>

                    <div>

                        <strong>

                            Statut actuel

                        </strong>

                        <p id="publicationResume">

                            {{ $options[$status][2] ?? '' }}

                        </p>

                    </div>

                </div>

            </div>

        </div>

    </div>

</div>
