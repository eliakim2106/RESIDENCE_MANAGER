<div class="wizard-stepper">

    <!-- Ligne de fond -->

    <div class="stepper-track"></div>

    <!-- Barre de progression -->

    <div
        id="progressionBarre"
        class="stepper-progress">
    </div>

    <!-- Etapes -->

    <div class="stepper-items">

        @foreach (['Informations', 'Localisation', 'Accueil & conditions', 'Médias', 'Publication', 'SEO'] as $index => $title)

            <div
                class="etape"
                data-step="{{ $index + 1 }}">

                <div class="etape-cercle">

                    {{ $index + 1 }}

                </div>

                <span>

                    {{ $title }}

                </span>

            </div>

        @endforeach

    </div>

</div>
