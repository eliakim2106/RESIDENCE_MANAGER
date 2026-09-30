@php
    $status = old('status', $etablissement->exists ? ($etablissement->isPublished() ? 'actif' : 'inactif') : 'actif');
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

                    Choisissez si cet établissement est immédiatement visible sur la plateforme.

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

                                L'établissement sera immédiatement visible et pourra recevoir des réservations.

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

                                L'établissement sera enregistré mais restera invisible jusqu'à son activation.

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

                            L'établissement sera publié dès son enregistrement.

                        </p>

                    </div>

                </div>

            </div>

        </div>

    </div>

</div>
